<?php
/**
 * Hero slide admin columns.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'manage_anishop_hero_slide_posts_columns',
	function ( $columns ) {
		$columns = array(
			'cb'       => $columns['cb'],
			'thumb'    => 'تصویر',
			'title'    => 'عنوان',
			'order'    => 'ترتیب',
			'cta'      => 'CTA',
			'date'     => 'تاریخ',
		);
		return $columns;
	}
);

add_action(
	'manage_anishop_hero_slide_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'thumb' === $column ) {
			if ( has_post_thumbnail( $post_id ) ) {
				echo get_the_post_thumbnail(
					$post_id,
					array( 96, 54 ),
					array()
				);
			} else {
				echo '—';
			}
		}

		if ( 'order' === $column ) {
			echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
		}

		if ( 'cta' === $column ) {
			$url = get_post_meta( $post_id, ANISHOP_HERO_META_CTA_URL, true );
			echo $url ? '<span dir="ltr">' . esc_html( $url ) . '</span>' : '—';
		}
	},
	10,
	2
);

add_filter(
	'manage_edit-anishop_hero_slide_sortable_columns',
	function ( $columns ) {
		$columns['order'] = 'menu_order';
		return $columns;
	}
);
