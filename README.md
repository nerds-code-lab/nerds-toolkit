# Nerds Toolkit

Open-source WordPress toolkit for reusable site layouts, shared business data, accessible navigation, Elementor Free widgets and optional WooCommerce catalogue tools. It is theme-independent and designed for use across multiple client projects.

Requires WordPress 6.6+ and PHP 8.2+. Elementor, WooCommerce and Contact Form 7 are optional integrations. No paid widgets, tracking, telemetry or remote fonts are included. Assets load only when their components are used.

## Editing a site without code

1. Open **Nerds Lab → Business details** to enter telephone, email, address, hours and social/directions links.
2. Set the logo and site name under **Appearance → Customize → Site Identity**. Create navigation menus under **Appearance → Menus**.
3. Open **Nerds Lab → Site layouts** and create a header or footer. Use the WordPress editor or **Edit with Elementor**.
4. In Elementor, use **Site identity**, **WordPress menu** and **Business contact** widgets to reference the site's shared information. Other Elementor Free widgets can compose the rest of the layout.
5. Publish the layout. Select it under the theme's header/footer Customizer controls. An unpublished, trashed, password-protected or missing layout never renders publicly; the theme uses its fallback.

## Theme setup dashboard

Themes can declare WordPress.org plugins for the **Nerds Toolkit → Dashboard** screen. The dashboard reports whether each plugin is missing, installed or active and uses WordPress's native nonce-protected installation and activation actions.

```php
add_filter( 'nerds_toolkit_recommended_plugins', static function( array $plugins ): array {
	$plugins['elementor'] = array(
		'name' => 'Elementor',
		'file' => 'elementor/elementor.php',
		'required' => true,
		'description' => 'Page editing with Elementor Free.',
	);
	return $plugins;
} );
```

The array key must be the WordPress.org plugin slug. `file` is the installed plugin basename. This API intentionally installs only from WordPress.org; themes must not silently download arbitrary remote packages.

Only administrators (users with `manage_options`) can create, edit, publish or access layouts through REST. Logged-out requests cannot preview layouts or read their REST endpoints. Elementor preview is allowed only for the authenticated administrator editing the requested layout. Layouts are excluded from search, archives and navigation selectors.

Native WordPress editor equivalents use the Shortcode block:

```text
[nerds_lab_identity show_tagline="yes"]
[nerds_lab_menu menu="12" label="Main navigation" mobile_toggle="yes" toggle_label="Menu"]
[nerds_lab_contact fields="phone,email,address,hours,map_url" heading="Contact"]
```

Use the actual WordPress menu ID in the menu shortcode. Elementor provides a menu dropdown instead. Native shortcodes inherit the site's styles; custom widget style controls are available inside Elementor. At viewport widths of 782px or less, navigation collapses behind a configurable button. Set `mobile_toggle="no"` in the shortcode or disable **Collapsible menu on mobile** in Elementor to retain an always-visible menu. Escape closes the open mobile menu and returns focus to its button. Desktop and no-JavaScript navigation remain visible, including submenu links. This is a simple disclosure menu, not a mega-menu or modal drawer.

## Public contracts

- `nerds_lab_render_layout(int $id): string`: returns published `nl_layout` markup, using Elementor's document renderer when available, or the normal WordPress content filters otherwise. Invalid, unpublished, password-protected and recursive references return `''`. Restores post context in `finally`.
- `nerds_lab_get_business_settings(): array`: returns the sanitized `nerds_lab_business` option with all supported keys: `phone`, `email`, `address`, `hours`, `map_url`, `facebook_url`, `instagram_url`. Missing values are empty strings.
- `nerds_lab_contact_markup(array $fields, string $heading = ''): string`: renders only populated, recognized fields with escaped text and URLs.
- Theme-owned selectors: `nl_header_layout` and `nl_footer_layout` theme mods. This plugin does not alter them.
- Elementor widget names: `nerds-lab-contact`, `nerds-lab-menu`, `nerds-lab-identity`.

## Persistence and maintenance

Business information and layouts live in WordPress, independently of the active theme. Deactivation or removal does not delete stored options or posts. Back up the database and uploads with the theme/plugin files; copying source alone does not transfer editor content. Disabling this plugin removes its shortcode/widget rendering until reactivated. Disabling Elementor falls back to stored WordPress content; Elementor-specific visual compositions still require Elementor.

Business inputs use the WordPress Settings API (`manage_options`, nonce verification, field sanitizers). No arbitrary CSS, JavaScript, PHP or analytics fields are exposed. Administrators retain WordPress's normal trusted HTML capabilities; this plugin does not grant new unfiltered HTML privileges. No LocalBusiness schema is inferred or emitted automatically; configure the accurate entity data in the site's chosen SEO plugin.

## References

- https://developers.elementor.com/docs/widgets/add-new-widget/
- https://elementor.com/help/does-elementor-work-with-posts-and-custom-post-types/
- https://developer.wordpress.org/plugins/settings/settings-api/
- https://developer.wordpress.org/reference/functions/register_post_type/

## Optional WooCommerce catalog

Under **WooCommerce → Settings → Products → Κατάλογος προϊόντων**, enable catalogue mode, edit the inquiry label and choose a fallback contact page. It is off by default. Catalogue mode hides frontend prices and sale badges, disables classic and Store API purchases, redirects cart/checkout to the shop and omits offers from WooCommerce product schema. Stored product prices and order data are preserved. Deactivating Nerds Toolkit removes this policy, so keep it active when the site must remain a catalogue.

Products, categories, images, attributes, descriptions, gallery, SKU and variations use native WooCommerce administration. Inquiries open the visitor's email app with product information, using the shared business email. Without an email, the published contact page is used. No message is sent by this plugin.

`[nerds_lab_catalog limit="6" columns="3" category="car-paints"]` renders a current WooCommerce product collection inside an Elementor Shortcode widget or WordPress Shortcode block. Category is optional. This needs no Elementor Pro and returns empty output when WooCommerce is absent.

Switching catalog mode off restores normal WooCommerce purchasing; configure real prices/stock, payments, tax, shipping, policies and test checkout before operating an eshop. Third-party feeds and external commerce plugins need their own verification.

The **Προϊόντα καταλόγου** Elementor Free widget exposes category, count, carousel and accessible label controls. Use `carousel="yes"` in the shortcode for the same behavior. Carousel assets are local; no autoplay or duplicated slides. Arrows, native touch scrolling, keyboard arrows/Home/End and reduced motion are supported. With JavaScript disabled, products remain scrollable links.
