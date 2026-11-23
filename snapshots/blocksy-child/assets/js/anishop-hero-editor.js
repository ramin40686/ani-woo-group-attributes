( function ( blocks, element, i18n ) {
	'use strict';

	const el = element.createElement;
	const __ = i18n.__;

	blocks.registerBlockType(
		'anishop/hero-slider',
		{
			apiVersion: 3,
			title: __( 'اسلایدر هیرو', 'anishop' ),
			description: __( 'اسلایدر هیروی صفحه اصلی آنی شاپ.', 'anishop' ),
			category: 'design',
			icon: 'images-alt2',
			attributes: {},
			edit: function () {
				return el(
					'div',
					{
						className: 'anishop-hero-slider-editor-placeholder',
						dir: 'rtl',
					},
					el(
						'strong',
						null,
						__( 'اسلایدر هیرو', 'anishop' )
					),
					el(
						'p',
						null,
						__( 'اسلایدهای منتشرشده از بخش «اسلایدر هیرو» در پیشخوان مدیریت می‌شوند.', 'anishop' )
					)
				);
			},
			save: function () {
				return null;
			},
		}
	);
} )( window.wp.blocks, window.wp.element, window.wp.i18n );
