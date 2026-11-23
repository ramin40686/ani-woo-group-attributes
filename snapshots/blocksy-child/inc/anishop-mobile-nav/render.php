<?php
/**
 * Anishop mobile bottom navigation — render.
 *
 * Renders a semantic, mobile-only bottom navigation bar. The active state,
 * account state and cart count are all derived server-side from the current
 * WordPress / WooCommerce request context (no JavaScript required).
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build the list of bottom navigation items with their active state.
 *
 * B6-15 Part 2, Step 3: the items are now driven by the validated
 * configuration from the Step-1 configuration layer
 * (anishop_mobile_nav_get_config()). When the Theme Mod is absent the
 * defaults are used, which reproduce the exact pre-B6-15 five-item navbar.
 *
 * The built-in dynamic behavior (URL resolution, active-state detection, the
 * account login-state label and the cart count) is preserved per item type;
 * only the label, icon, order and enabled state become configurable.
 *
 * @return array[]
 */
function anishop_mobile_nav_items() {
	$config = anishop_mobile_nav_get_config();

	if ( empty( $config['enabled'] ) ) {
		return array();
	}

	$items = array();
	foreach ( $config['items'] as $config_item ) {
		if ( empty( $config_item['enabled'] ) ) {
			continue;
		}
		$items[] = anishop_mobile_nav_resolve_item( $config_item );
	}

	// B6-15 custom active-state parity: a custom link item has no built-in
	// active state, so it derives one from the SAME mechanism the system items
	// use (the `active` flag that becomes the `is-active` class +
	// aria-current="page"), by matching its configured URL against the current
	// request. A link item yields to any active system item, and at most one
	// link item is active, so the navbar keeps its existing "at most one
	// active item" semantics.
	$system_active = false;
	foreach ( $items as $item ) {
		if ( 'link' !== $item['type'] && ! empty( $item['active'] ) ) {
			$system_active = true;
			break;
		}
	}
	$link_active = false;
	foreach ( $items as &$item ) {
		if ( 'link' === $item['type'] ) {
			$item['active'] = ! $system_active && ! $link_active && anishop_mobile_nav_is_current_url( $item['url'] );
			if ( $item['active'] ) {
				$link_active = true;
			}
		}
	}
	unset( $item );

	return $items;
}

/**
 * Return the built-in (pre-B6-15) dynamic behavior for a supported item type.
 *
 * This preserves the exact existing behavior: URL resolution, active-state
 * detection, the account login-state label and the cart count. `link` items
 * have no built-in behavior (empty label/URL, never active) — they rely
 * entirely on the validated administrator-provided values.
 *
 * @param string $type A supported item type.
 * @return array
 */
function anishop_mobile_nav_builtin_item( $type ) {
	switch ( $type ) {
		case 'home':
			return array(
				'label'  => __( 'خانه', 'anishop' ),
				'url'    => home_url( '/' ),
				'active' => is_front_page(),
			);

		case 'shop':
			return array(
				'label'  => __( 'دسته‌بندی‌ها', 'anishop' ),
				'url'    => wc_get_page_permalink( 'shop' ),
				'active' => is_shop() || is_product_category() || is_product_tag(),
			);

		case 'cart':
			$count = 0;
			if ( function_exists( 'WC' ) && null !== WC() && null !== WC()->cart ) {
				$count = (int) WC()->cart->get_cart_contents_count();
			}
			return array(
				'label'  => __( 'سبد خرید', 'anishop' ),
				'url'    => wc_get_page_permalink( 'cart' ),
				'active' => is_cart(),
				'count'  => $count,
			);

		case 'blog':
			$blog_url = get_permalink( (int) get_option( 'page_for_posts' ) );
			$blog_url = $blog_url ? $blog_url : home_url( '/blog/' );
			return array(
				'label'  => __( 'بلاگ', 'anishop' ),
				'url'    => $blog_url,
				'active' => ( is_home() && ! is_front_page() ) || is_singular( 'post' ) || is_category() || is_tag(),
			);

		case 'account':
			$logged_in = is_user_logged_in();
			return array(
				'label'     => $logged_in ? __( 'حساب کاربری', 'anishop' ) : __( 'ورود', 'anishop' ),
				'url'       => wc_get_page_permalink( 'myaccount' ),
				'active'    => function_exists( 'is_account_page' ) && is_account_page(),
				'logged_in' => $logged_in,
			);

		case 'link':
		default:
			return array(
				'label'  => '',
				'url'    => '',
				'active' => false,
			);
	}
}

/**
 * Resolve a single validated configuration item into a render item.
 *
 * The built-in dynamic behavior for the item's type is the base; the
 * configured label, icon and URL are applied on top. An empty configured
 * label keeps the built-in (possibly dynamic, e.g. account) label, and an
 * empty configured URL keeps the built-in dynamic URL. The item key is the
 * configured ID (the cart ID is always `cart`, enforced by the config layer).
 *
 * @param array $config_item A validated item from anishop_mobile_nav_get_config().
 * @return array
 */
function anishop_mobile_nav_resolve_item( $config_item ) {
	$builtin = anishop_mobile_nav_builtin_item( $config_item['type'] );

	$label = ( '' !== $config_item['label'] ) ? $config_item['label'] : $builtin['label'];
	$url   = ( '' !== $config_item['url'] ) ? $config_item['url'] : $builtin['url'];

	$item = array(
		'key'    => $config_item['id'],
		'type'   => $config_item['type'],
		'label'  => $label,
		'url'    => $url,
		'icon'   => $config_item['icon'],
		'active' => $builtin['active'],
	);

	if ( isset( $builtin['count'] ) ) {
		$item['count'] = $builtin['count'];
	}
	if ( isset( $builtin['logged_in'] ) ) {
		$item['logged_in'] = $builtin['logged_in'];
	}

	return $item;
}

/**
 * Normalize a navigation URL to a comparable site-relative path.
 *
 * Used to decide whether a custom link item points at the current page. The
 * normalization makes the comparison deterministic and safe:
 *  - site-relative URLs are made absolute against the home URL;
 *  - the query string and fragment are dropped (path only);
 *  - the home path prefix is stripped (subdirectory installs);
 *  - percent-encoding is neutralized (rawurldecode);
 *  - the result is a single leading slash with no trailing slash (root = "/").
 *
 * @param string $url A URL (absolute or site-relative).
 * @return string|null The normalized path, or null when the URL is empty/invalid.
 */
function anishop_mobile_nav_normalize_nav_url( $url ) {
	if ( ! is_string( $url ) || '' === trim( $url ) ) {
		return null;
	}

	$url = esc_url_raw( $url );

	// Make a site-relative URL absolute (leave protocol-relative URLs alone).
	if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
		$url = home_url( $url );
	}

	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( ! is_string( $path ) || '' === $path ) {
		return null;
	}

	// Strip the home path prefix (for subdirectory installs).
	$home_path = wp_parse_url( home_url(), PHP_URL_PATH );
	if ( is_string( $home_path ) && '' !== $home_path && 0 === strpos( $path, $home_path ) ) {
		$path = substr( $path, strlen( $home_path ) );
	}

	// Neutralize percent-encoding differences.
	$path = rawurldecode( $path );

	// Canonical form: single leading slash, no trailing slash (root = "/").
	$path = '/' . ltrim( $path, '/' );
	if ( strlen( $path ) > 1 ) {
		$path = rtrim( $path, '/' );
	}

	return $path;
}

/**
 * The normalized path of the current request.
 *
 * Prefers the WordPress request (the pretty-permalink path, without the query
 * string); falls back to the raw REQUEST_URI path. The front page normalizes
 * to "/".
 *
 * @return string
 */
function anishop_mobile_nav_current_nav_path() {
	$request = '';
	if ( isset( $GLOBALS['wp']->request ) && is_string( $GLOBALS['wp']->request ) ) {
		$request = $GLOBALS['wp']->request;
	}
	if ( '' === $request && isset( $_SERVER['REQUEST_URI'] ) ) {
		$request = (string) wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
	}

	$normalized = anishop_mobile_nav_normalize_nav_url( home_url( $request ) );

	return null === $normalized ? '/' : $normalized;
}

/**
 * Whether a navigation URL points at the current page.
 *
 * Compares the normalized URL against the normalized current request path.
 *
 * @param string $url A URL (absolute or site-relative).
 * @return bool
 */
function anishop_mobile_nav_is_current_url( $url ) {
	$target = anishop_mobile_nav_normalize_nav_url( $url );
	if ( null === $target ) {
		return false;
	}

	return $target === anishop_mobile_nav_current_nav_path();
}

/**
 * Return a lightweight inline SVG for the given icon key.
 *
 * Built-in icons are minimal stroke-based 24x24 paths (no external library, no
 * data URI). A project icon (`project:<id>`) is resolved to the sanitized
 * contents of the validated assets/icons/<id>.svg file (see icon-assets.php);
 * an unresolvable identifier safely returns an empty string.
 *
 * @param string $key Icon key (built-in key or `project:<id>`).
 * @return string
 */
function anishop_mobile_nav_icon( $key ) {
	$common = 'xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"';
	$icons  = array(
		'home' => '<svg ' . $common . '><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
		'grid' => '<svg ' . $common . '><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
		'cart' => '<svg ' . $common . '><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
		'blog' => '<svg ' . $common . '><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
		'user' => '<svg ' . $common . '><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
	);

	if ( isset( $icons[ $key ] ) ) {
		return $icons[ $key ];
	}

	// Project icon: resolve the validated assets/icons/<id>.svg file.
	if ( is_string( $key ) && 0 === strpos( $key, anishop_mobile_nav_project_icon_prefix() ) ) {
		return anishop_mobile_nav_project_icon_svg( $key );
	}

	return '';
}

/**
 * Render the mobile bottom navigation markup.
 *
 * Hooked to wp_footer. Only visible on mobile/tablet widths (see CSS).
 *
 * @return void
 */
function anishop_mobile_nav_render() {
	$items = anishop_mobile_nav_items();
	if ( empty( $items ) ) {
		return;
	}

	// B6-15 appearance presets: the validated appearance configuration maps to a
	// fixed set of CSS preset classes (anishop_mobile_nav_appearance_classes()).
	// The actual CSS values live in anishop-mobile-nav.css; only the validated
	// preset identifiers reach the markup (no arbitrary CSS or color value).
	$config             = anishop_mobile_nav_get_config();
	$appearance_classes = anishop_mobile_nav_appearance_classes( $config['appearance'] );
	$nav_classes        = 'anishop-mobile-nav' . ( '' !== $appearance_classes ? ' ' . $appearance_classes : '' );
	?>
	<nav class="<?php echo esc_attr( $nav_classes ); ?>" aria-label="<?php esc_attr_e( 'ناوبری اصلی موبایل', 'anishop' ); ?>">
		<?php
		foreach ( $items as $item ) {
			$classes = array( 'anishop-mobile-nav__item' );
			$attrs   = '';

			if ( ! empty( $item['active'] ) ) {
				$classes[] = 'is-active';
				$attrs    .= ' aria-current="page"';
			}
			if ( ! empty( $item['logged_in'] ) ) {
				$classes[] = 'is-logged-in';
			}

			// Expose the cart count in the accessible name (badge is visual-only).
			if ( 'cart' === $item['key'] && ! empty( $item['count'] ) ) {
				$attrs .= ' aria-label="' . esc_attr( $item['label'] . ' (' . number_format_i18n( $item['count'] ) . ')' ) . '"';
			}
			?>
			<a class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" href="<?php echo esc_url( $item['url'] ); ?>" data-anishop-nav="<?php echo esc_attr( $item['key'] ); ?>"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<span class="anishop-mobile-nav__icon" aria-hidden="true"><?php echo anishop_mobile_nav_icon( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="anishop-mobile-nav__label"><?php echo esc_html( $item['label'] ); ?></span>
				<?php if ( 'cart' === $item['key'] && ! empty( $item['count'] ) ) : ?>
					<span class="anishop-mobile-nav__badge" aria-hidden="true"><?php echo esc_html( number_format_i18n( $item['count'] ) ); ?></span>
				<?php endif; ?>
			</a>
			<?php
		}
		?>
	</nav>
	<?php
}
