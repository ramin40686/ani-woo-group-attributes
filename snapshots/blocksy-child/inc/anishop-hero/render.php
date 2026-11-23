<?php
/**
 * Server-side Hero Slider rendering.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function anishop_hero_get_slides() {
	return get_posts(
		array(
			'post_type'      => 'anishop_hero_slide',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
}

function anishop_hero_render( $attributes = array(), $content = '', $block = null ) {
	$slides = anishop_hero_get_slides();

	$slides = array_values(
		array_filter(
			$slides,
			static function ( $slide ) {
				return has_post_thumbnail( $slide->ID );
			}
		)
	);

	if ( empty( $slides ) ) {
		return '';
	}

	wp_enqueue_style( 'anishop-hero-slider' );
	wp_enqueue_script( 'anishop-hero-slider' );

	$count = count( $slides );
	$label = 'اسلایدر صفحه اصلی';

	ob_start();
	?>
	<section
		class="anishop-hero-slider<?php echo 1 === $count ? ' anishop-hero-slider--single' : ''; ?>"
		data-anishop-hero-slider
		data-slide-count="<?php echo esc_attr( $count ); ?>"
		aria-label="<?php echo esc_attr( $label ); ?>"
		dir="rtl"
	>
		<div class="anishop-hero-slider__viewport">
			<div class="anishop-hero-slider__track">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<?php
					$is_active   = 0 === $index;
					$eyebrow     = get_post_meta( $slide->ID, ANISHOP_HERO_META_EYEBROW, true );
					$description = get_post_meta( $slide->ID, ANISHOP_HERO_META_DESCRIPTION, true );
					$cta_text    = get_post_meta( $slide->ID, ANISHOP_HERO_META_CTA_TEXT, true );
					$cta_url     = get_post_meta( $slide->ID, ANISHOP_HERO_META_CTA_URL, true );
					?>
					<article
						class="anishop-hero-slider__slide<?php echo $is_active ? ' is-active' : ''; ?>"
						data-slide-index="<?php echo esc_attr( $index ); ?>"
						role="group"
						aria-roledescription="slide"
						aria-label="<?php echo esc_attr( sprintf( 'اسلاید %d از %d', $index + 1, $count ) ); ?>"
						aria-hidden="<?php echo $is_active ? 'false' : 'true'; ?>"
					>
						<?php if ( '' !== $cta_url ) : ?>
							<a
								class="anishop-hero-slider__slide-link"
								href="<?php echo esc_url( $cta_url ); ?>"
								target="_blank"
								rel="noopener noreferrer"
								tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
								aria-label="<?php echo esc_attr( '' !== $cta_text ? $cta_text : get_the_title( $slide ) ); ?>"
							></a>
						<?php endif; ?>

						<div class="anishop-hero-slider__media">
							<?php
							echo wp_get_attachment_image(
								get_post_thumbnail_id( $slide->ID ),
								'large',
								false,
								array(
									'class'         => 'anishop-hero-slider__image',
									'loading'       => $is_active ? 'eager' : 'lazy',
									'fetchpriority' => $is_active ? 'high' : 'auto',
									'decoding'      => 'async',
								)
							);
							?>
						</div>

						<div class="anishop-hero-slider__overlay" aria-hidden="true"></div>

						<div class="anishop-hero-slider__content">
							<?php if ( '' !== $eyebrow ) : ?>
								<p class="anishop-hero-slider__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
							<?php endif; ?>

							<?php if ( $is_active ) : ?>
								<h1 class="anishop-hero-slider__title"><?php echo esc_html( get_the_title( $slide ) ); ?></h1>
							<?php else : ?>
								<h2 class="anishop-hero-slider__title"><?php echo esc_html( get_the_title( $slide ) ); ?></h2>
							<?php endif; ?>

							<?php if ( '' !== $description ) : ?>
								<p class="anishop-hero-slider__description"><?php echo esc_html( $description ); ?></p>
							<?php endif; ?>

							<?php if ( '' !== $cta_text && '' !== $cta_url ) : ?>
								<a class="anishop-hero-slider__cta" href="<?php echo esc_url( $cta_url ); ?>" target="_blank" rel="noopener noreferrer" tabindex="<?php echo $is_active ? '0' : '-1'; ?>">
									<span><?php echo esc_html( $cta_text ); ?></span>
									<span class="anishop-hero-slider__cta-icon" aria-hidden="true"></span>
								</a>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>

		<?php if ( $count > 1 ) : ?>
			<button class="anishop-hero-slider__nav anishop-hero-slider__nav--prev" type="button" aria-label="اسلاید قبلی">
				<span aria-hidden="true" class="anishop-hero-slider__chevron anishop-hero-slider__chevron--prev"></span>
			</button>

			<button class="anishop-hero-slider__nav anishop-hero-slider__nav--next" type="button" aria-label="اسلاید بعدی">
				<span aria-hidden="true" class="anishop-hero-slider__chevron anishop-hero-slider__chevron--next"></span>
			</button>

			<div class="anishop-hero-slider__controls">
				<div class="anishop-hero-slider__pagination" aria-label="انتخاب اسلاید"></div>
				<button class="anishop-hero-slider__playpause" type="button" aria-label="توقف پخش خودکار">
					<span class="anishop-hero-slider__pause-icon" aria-hidden="true"></span>
					<span class="anishop-hero-slider__play-icon" aria-hidden="true"></span>
				</button>
			</div>

			<p class="anishop-hero-slider__live" aria-live="polite" aria-atomic="true"></p>
		<?php endif; ?>
	</section>
	<?php
	return ob_get_clean();
}
