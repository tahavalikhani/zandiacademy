<?php
/**
 * Instructors.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

// Loaded bare from the homepage, or with flags from template-section.php.
$zandi_args = isset( $args ) && is_array( $args ) ? $args : array();
?>

<section class="section" id="teachers" aria-labelledby="teachers-title">
	<div class="container">
		<?php
		zandi_maybe_section_heading(
			$zandi_args,
			array(
				'id'          => 'teachers',
				'eyebrow'     => 'درباره من',
				'title'       => 'من کی‌ام و چرا این دوره‌ها رو ساختم',
				'description' => '',
			)
		);
		?>

		<div class="teachers__grid reveal-group">
			<?php foreach ( zandi_teachers() as $index => $teacher ) : ?>
				<div class="reveal reveal--scale">
					<article class="card card--interactive teacher-card">
						<div class="teacher-card__head">
							<?php
							zandi_avatar(
								$teacher['name'],
								array(
									'image' => $teacher['image'],
									'size'  => 'lg',
									'index' => $index,
								)
							);
							?>
							<span class="teacher-card__identity">
								<span class="teacher-card__name"><?php echo esc_html( $teacher['name'] ); ?></span>
								<span class="teacher-card__role"><?php echo esc_html( $teacher['role'] ); ?></span>
							</span>
						</div>

						<p class="teacher-card__credential">
							<?php zandi_icon( 'graduation' ); ?>
							<span dir="auto"><?php echo esc_html( $teacher['credential'] ); ?></span>
						</p>

						<p class="teacher-card__bio"><?php echo zandi_bidi( $teacher['bio'] ); ?></p>

						<?php zandi_badge( $teacher['focus'], 'neutral', array( 'class' => 'teacher-card__focus' ) ); ?>
					</article>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
