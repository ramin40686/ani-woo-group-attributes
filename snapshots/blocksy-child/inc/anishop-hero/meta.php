<?php
/**
 * Hero slide metadata.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ANISHOP_HERO_META_EYEBROW     = '_anishop_hero_eyebrow';
const ANISHOP_HERO_META_DESCRIPTION = '_anishop_hero_description';
const ANISHOP_HERO_META_CTA_TEXT    = '_anishop_hero_cta_text';
const ANISHOP_HERO_META_CTA_URL     = '_anishop_hero_cta_url';

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box(
			'anishop_hero_slide_settings',
			'محتوای اسلاید',
			'anishop_hero_render_meta_box',
			'anishop_hero_slide',
			'normal',
			'high'
		);
	}
);

function anishop_hero_render_meta_box( $post ) {
	wp_nonce_field( 'anishop_hero_save_meta', 'anishop_hero_meta_nonce' );

	$eyebrow     = get_post_meta( $post->ID, ANISHOP_HERO_META_EYEBROW, true );
	$description = get_post_meta( $post->ID, ANISHOP_HERO_META_DESCRIPTION, true );
	$cta_text    = get_post_meta( $post->ID, ANISHOP_HERO_META_CTA_TEXT, true );
	$cta_url     = get_post_meta( $post->ID, ANISHOP_HERO_META_CTA_URL, true );
	?>
	<p>
		<label for="anishop_hero_eyebrow"><strong>Eyebrow</strong> <span class="description">(اختیاری)</span></label>
		<input class="widefat" type="text" id="anishop_hero_eyebrow" name="anishop_hero_eyebrow" value="<?php echo esc_attr( $eyebrow ); ?>">
	</p>

	<p>
		<label for="anishop_hero_description"><strong>توضیح کوتاه</strong> <span class="description">(اختیاری)</span></label>
		<textarea class="widefat" rows="4" id="anishop_hero_description" name="anishop_hero_description"><?php echo esc_textarea( $description ); ?></textarea>
	</p>

	<div>
		<p>
			<label for="anishop_hero_cta_text"><strong>متن CTA</strong> <span class="description">(اختیاری)</span></label>
			<input class="widefat" type="text" id="anishop_hero_cta_text" name="anishop_hero_cta_text" value="<?php echo esc_attr( $cta_text ); ?>">
		</p>

		<p>
			<label for="anishop_hero_cta_url"><strong>URL دکمه</strong> <span class="description">(اختیاری)</span></label>
			<input class="widefat" type="url" dir="ltr" id="anishop_hero_cta_url" name="anishop_hero_cta_url" value="<?php echo esc_attr( $cta_url ); ?>">
		</p>
	</div>

	<p class="description">
		تصویر اسلاید از «تصویر شاخص» تأمین می‌شود. ترتیب نمایش از فیلد «ترتیب» در ویژگی‌های برگه تنظیم می‌شود.
	</p>
	<?php
}

add_action(
	'save_post_anishop_hero_slide',
	function ( $post_id ) {
		if ( ! isset( $_POST['anishop_hero_meta_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['anishop_hero_meta_nonce'] ) ), 'anishop_hero_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta(
			$post_id,
			ANISHOP_HERO_META_EYEBROW,
			sanitize_text_field( wp_unslash( $_POST['anishop_hero_eyebrow'] ?? '' ) )
		);

		update_post_meta(
			$post_id,
			ANISHOP_HERO_META_DESCRIPTION,
			sanitize_textarea_field( wp_unslash( $_POST['anishop_hero_description'] ?? '' ) )
		);

		update_post_meta(
			$post_id,
			ANISHOP_HERO_META_CTA_TEXT,
			sanitize_text_field( wp_unslash( $_POST['anishop_hero_cta_text'] ?? '' ) )
		);

		update_post_meta(
			$post_id,
			ANISHOP_HERO_META_CTA_URL,
			esc_url_raw( wp_unslash( $_POST['anishop_hero_cta_url'] ?? '' ) )
		);
	}
);
