<?php
/**
 * FAQ accordion.
 *
 * A1 carries one extra question ("هیچی بلد نیستم…") which leads, because a
 * beginner asking it is the whole point of that page.
 *
 * Two questions from the copy document are deliberately absent — see the note
 * on zandi_course_faq() in inc/courses.php.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course = $args['course'];
$faqs   = array_merge( $course['extra_faq'], zandi_course_faq() );
?>

<section class="c-section" id="faq" aria-labelledby="faq-title">
	<div class="c-container">
		<div class="c-section__head">
			<h2 class="c-section__title" id="faq-title">هر چی ممکنه بپرسی</h2>
		</div>

		<div class="accordion" data-accordion>
			<?php foreach ( $faqs as $index => $faq ) : ?>
				<?php
				$is_open    = 0 === $index;
				$trigger_id = 'faq-trigger-' . $index;
				$panel_id   = 'faq-panel-' . $index;
				?>
				<div class="accordion__item<?php echo $is_open ? ' is-open' : ''; ?>">
					<h3>
						<button
							type="button"
							class="accordion__trigger"
							id="<?php echo esc_attr( $trigger_id ); ?>"
							aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
							aria-controls="<?php echo esc_attr( $panel_id ); ?>"
						>
							<span><?php echo zandi_bidi( $faq['q'] ); ?></span>
							<span class="accordion__chevron"><?php zandi_icon( 'chevronDown' ); ?></span>
						</button>
					</h3>

					<div
						class="accordion__panel"
						id="<?php echo esc_attr( $panel_id ); ?>"
						role="region"
						aria-labelledby="<?php echo esc_attr( $trigger_id ); ?>"
					>
						<div>
							<p class="accordion__answer"><?php echo zandi_bidi( $faq['a'] ); ?></p>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
