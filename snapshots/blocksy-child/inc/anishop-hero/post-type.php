<?php
/**
 * Hero slide post type.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	function () {
		register_post_type(
			'anishop_hero_slide',
			array(
				'labels' => array(
					'name'               => 'اسلایدهای هیرو',
					'singular_name'      => 'اسلاید هیرو',
					'menu_name'          => 'اسلایدر هیرو',
					'add_new'            => 'افزودن اسلاید',
					'add_new_item'       => 'افزودن اسلاید جدید',
					'edit_item'          => 'ویرایش اسلاید',
					'new_item'           => 'اسلاید جدید',
					'view_item'          => 'مشاهده اسلاید',
					'search_items'       => 'جستجوی اسلایدها',
					'not_found'          => 'اسلایدی یافت نشد',
					'not_found_in_trash' => 'اسلایدی در زباله‌دان یافت نشد',
					'all_items'          => 'همه اسلایدها',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'has_archive'         => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'menu_position'       => 25,
				'menu_icon'           => 'dashicons-images-alt2',
				'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
			)
		);
	}
);
