<?php
/**
 * Introduction video.
 *
 * The clip itself is not in this repository — it is uploaded to the Media
 * Library as `course-{slug}-intro.mp4` and resolved by zandi_course_video().
 * Until one exists for this course the helper returns '' and zandi_video()
 * draws its «به‌زودی» placeholder, so a course with no video recorded yet
 * renders exactly as it did before.
 *
 * The three jumps below it are the questions somebody has the moment the video
 * ends — see zandi_course_jump_links(). They are plain anchors: the smooth
 * travel is `html { scroll-behavior: smooth }` in style.css, which a script
 * would only duplicate and would duplicate worse, because that rule already
 * stands down under prefers-reduced-motion.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course = $args['course'];
$video  = zandi_course_video( $course['slug'], 'intro' );
$meta   = zandi_course_video_meta( $course['slug'], 'intro' );
?>

<section class="c-section" id="intro" aria-labelledby="intro-title">
	<div class="c-container">
		<div class="c-section__head reveal">
			<h2 class="c-section__title" id="intro-title">یک دقیقه وقت بذار، بذار خودم برات بگم 👋</h2>
			<p class="c-section__lead">
				<?php
				echo zandi_bidi(
					! empty( $course['intro_lead'] )
						? $course['intro_lead']
						: 'توی این ویدیو کوتاه توضیح می‌دم دوره چطور پیش می‌ره و چرا این روش با چیزی که تا حالا امتحان کردی فرق داره.'
				);
				?>
			</p>
		</div>

		<?php
		zandi_video(
			array(
				'file'   => $video,
				'poster' => $video ? zandi_course_video_poster( $course['slug'], 'intro' ) : '',
				'title'  => $meta['name'],
				'note'   => 'ویدیوی معرفی به‌زودی اینجا قرار می‌گیرد',
				'class'  => 'reveal reveal--scale',
			)
		);

		$zandi_jumps = zandi_course_jump_links( $course );

		if ( $zandi_jumps ) :
			?>
			<?php
			/*
			 * Two across, then one under them — the owner's arrangement. The
			 * third takes the full width rather than sitting alone in a column,
			 * so the block reads as a group of three and not as a two-up row
			 * with a stray beneath it.
			 *
			 * A <nav> with a name, because that is what it is: three links to
			 * places on this page. Without the label a screen reader announces
			 * a second unnamed navigation on a page that already has the site
			 * menu.
			 */
			?>
			<nav class="c-jumps reveal" aria-label="<?php echo esc_attr( 'میان‌بر به بخش‌های این صفحه' ); ?>">
				<?php foreach ( $zandi_jumps as $zandi_jump ) : ?>
					<a class="c-btn c-btn--ghost c-jumps__link" href="#<?php echo esc_attr( $zandi_jump['target'] ); ?>">
						<?php if ( ! empty( $zandi_jump['icon'] ) ) : ?>
							<span class="c-jumps__icon" aria-hidden="true"><?php zandi_icon( $zandi_jump['icon'] ); ?></span>
						<?php endif; ?>
						<span><?php echo esc_html( $zandi_jump['label'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</div>
</section>
