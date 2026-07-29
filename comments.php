<?php
/**
 * Comments.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

// Bail on password-protected posts the visitor has not unlocked.
if ( post_password_required() ) {
	return;
}
?>

<section class="comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="comments__title">
			<?php
			$zandi_count = (int) get_comments_number();

			printf(
				'%s دیدگاه',
				esc_html( zandi_fa_digits( number_format_i18n( $zandi_count ) ) )
			);
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
					'avatar_size' => 48,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'class'     => 'pagination',
				'prev_text' => zandi_get_icon( zandi_arrow_back() ),
				'next_text' => zandi_get_icon( zandi_arrow_forward() ),
			)
		);
		?>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_submit'  => 'submit',
			'title_reply'   => 'دیدگاه شما',
			'label_submit'  => 'ثبت دیدگاه',
			'comment_field' => '<p class="field"><label class="field__label" for="comment">دیدگاه</label><textarea class="field__control" id="comment" name="comment" rows="6" required></textarea></p>',
		)
	);
	?>
</section>
