<?php
/**
 * Who this course is for, and who it is not for.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course = $args['course'];
?>

<section class="c-section" id="fit" aria-labelledby="fit-title">
	<div class="c-container">
		<div class="c-section__head">
			<h2 class="c-section__title" id="fit-title">این دوره برای کیه</h2>
		</div>

		<div class="c-fit">
			<div class="c-fit__col">
				<h3 class="c-fit__title">مناسبه برای</h3>
				<ul class="c-fit__list">
					<?php foreach ( $course['who_for'] as $item ) : ?>
						<li>
							<?php zandi_mark( 'yes' ); ?>
							<span><?php echo zandi_bidi( $item ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="c-fit__col">
				<h3 class="c-fit__title">مناسب نیست برای</h3>
				<ul class="c-fit__list">
					<?php foreach ( $course['who_not_for'] as $item ) : ?>
						<li>
							<?php zandi_mark( 'no' ); ?>
							<span><?php echo zandi_bidi( $item ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
</section>
