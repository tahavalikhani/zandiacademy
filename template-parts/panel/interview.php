<?php
/**
 * The end-of-course interview.
 *
 * There is no scheduling system — the interview is arranged in Telegram, which
 * is the truth, so that is what this says. It becomes a real status block when
 * there is something to track.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$zandi_copy = zandi_panel_copy();
?>

<section class="c-section c-section--tight" aria-labelledby="interview-title">
	<h2 class="c-section__title" id="interview-title"><?php echo esc_html( $zandi_copy['interview_title'] ); ?></h2>

	<div class="c-card p-card">
		<p><?php echo esc_html( $zandi_copy['interview_body'] ); ?></p>
		<p class="p-card__note"><?php echo esc_html( $zandi_copy['interview_empty'] ); ?></p>
	</div>
</section>
