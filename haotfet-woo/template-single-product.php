<?php
/**
 * Haotfet single-product template — v3.1
 * Loaded by template_include at priority 99 — bypasses Elementor completely.
 * Calls get_header() / get_footer() so the theme nav/header/footer still render.
 *
 * Sections:
 *  0. Announce bar (opacity-fade rotator)
 *  1. Jump nav (sticky scroll links)
 *  2. Gallery  (1:1 main + scrollable thumbs)
 *  3. Product info block
 *  4. Size grid with slide-open detail panel + lightbox
 *  5. Price + Cart
 *  6. Benefits bar (horizontal rows, expandable)
 *  7. Videos section
 *  8. FAQ accordion (circular chevron)
 *  9. How-to-use (vertical steps list)
 * 10. Related products
 * 11. Lightbox overlay
 * 12. Sticky buy bar (mobile only)
 */
defined( 'ABSPATH' ) || exit;

/*
 * We bypass get_header() / get_footer() entirely and manage the page shell ourselves.
 * This guarantees that elementor_theme_do_location('header') is called directly,
 * independent of whether Elementor's need_override_location filter suppressed its
 * location-system initialisation for the 'single' slot.
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="https://gmpg.org/xfn/11">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
/* ── Header: Elementor Theme Builder first, hello-elementor dynamic-header as fallback ── */
if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'header' ) ) {
	do_action( 'get_header', null );
	if ( function_exists( 'hello_elementor_display_header_footer' ) && hello_elementor_display_header_footer() ) {
		if ( did_action( 'elementor/loaded' ) && function_exists( 'hello_header_footer_experiment_active' ) && hello_header_footer_experiment_active() ) {
			get_template_part( 'template-parts/dynamic-header' );
		} else {
			get_template_part( 'template-parts/header' );
		}
	}
}
?>
<?php

global $product;
while ( have_posts() ) : the_post();
	if ( ! is_a( $product, 'WC_Product' ) ) $product = wc_get_product( get_the_ID() );

	$pid        = get_the_ID();
	$sub        = get_post_meta( $pid, 'haot_subtitle',   true );
	$rv         = (float) get_post_meta( $pid, 'haot_rating_val', true );
	$rc         = (int)   get_post_meta( $pid, 'haot_rating_cnt', true );
	$desc       = get_post_meta( $pid, 'haot_desc',       true );
	$desc_full  = get_post_meta( $pid, 'haot_desc_full',  true );
	$ship       = get_post_meta( $pid, 'haot_shipping',   true );
	$sizes      = get_post_meta( $pid, 'haot_sizes',      true ) ?: [];
	$bens       = get_post_meta( $pid, 'haot_benefits',   true ) ?: [];
	$acc        = get_post_meta( $pid, 'haot_accordion',  true ) ?: [];
	$faq        = get_post_meta( $pid, 'haot_faq',        true ) ?: [];
	$how        = get_post_meta( $pid, 'haot_how_to',     true ) ?: [];
	$trust      = get_post_meta( $pid, 'haot_trust',      true ) ?: [];
	$announce   = get_post_meta( $pid, 'haot_announce',   true ) ?: [];
	$main_video = get_post_meta( $pid, 'haot_main_video', true );
	$videos     = get_post_meta( $pid, 'haot_videos',     true ) ?: [];

	// Defaults
	if ( empty( $announce ) ) {
		$announce = [
			'🏠 משלוח חינם עד הבית בקנייה מעל ₪299',
			'📦 איסוף חינם מנקודת איסוף מעל ₪169',
			'🌿 100% חומרים טבעיים וישראלי',
		];
	}
	if ( empty( $trust ) ) {
		$trust = [
			[ 'icon' => '🏅', 'label' => 'ישראלי 100%' ],
			[ 'icon' => '♻️', 'label' => 'עד 100 שימושים' ],
			[ 'icon' => '🌿', 'label' => '100% טבעי' ],
		];
	}
	if ( ! $ship ) {
		$ship = '✓ משלוח חינם מ-₪299 | ✓ 100% טבעי | ✓ תוצרת ישראל';
	}

	// Build gallery image set
	$main_img_id = $product->get_image_id();
	$gallery_ids = $product->get_gallery_image_ids();
	$all_ids     = array_merge( $main_img_id ? [ $main_img_id ] : [], $gallery_ids );

	// Fallback brand images
	$brand_imgs = [
		HF_URL . 'assets/brand/imgi_5_width_550.webp',
		HF_URL . 'assets/brand/imgi_6_width_510.webp',
		HF_URL . 'assets/brand/imgi_7_width_550.png',
		HF_URL . 'assets/brand/imgi_8_width_550.webp',
		HF_URL . 'assets/brand/imgi_9_width_550.png',
	];

?>
<!-- HF-TEMPLATE-V3-LOADED -->
<!-- ═══════════════════════════════════════ FONT + STYLES ═══ -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Assistant:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
/* ─── CSS Custom Properties ─── */
:root{
  --yellow:#F2B705;
  --yellow-lt:#FFF8E0;
  --dark:#1C1C1C;
  --cream:#FAFAF6;
  --border:#E4E1D8;
  --green:#2D6E3E;
  --white:#fff;
  --gray:#888;
  --hint:#9A9A9A;
  --mid:#555;
  --gray-lt:#F4F3EF;
  --savings-bg:#FFF0ED;
  --savings:#B53A2A;
  --r-sm:8px;--r-md:12px;--r-lg:18px;
  --elev:0 2px 8px rgba(0,0,0,.06),0 6px 24px rgba(0,0,0,.05);
  --elev-sm:0 1px 4px rgba(0,0,0,.08);
}

/* ─── Neutralise theme / Elementor wrappers ─── */
body.single-product .site-content,
body.single-product .site-content .container,
body.single-product .content-area,
body.single-product main#main,
body.single-product main.site-main,
body.single-product #primary,
body.single-product .woocommerce,
body.woocommerce-page .hf2-page{
  padding:0 !important;
  margin:0 !important;
  max-width:100% !important;
  width:100% !important;
  float:none !important;
}
/* Kill any Kirki/WP custom-header image that leaks through */
body.single-product .wp-custom-header,
body.single-product .custom-header-media,
body.single-product #wp-custom-header{
  display:none !important;
}

/* ─── Reset / Base ─── */
.hf2-page *,
.hf2-page *::before,
.hf2-page *::after{box-sizing:border-box;margin:0;padding:0}
.hf2-page{
  direction:rtl;
  font-family:'Assistant',sans-serif;
  font-size:15px;
  color:var(--dark);
  background:var(--cream);
  padding-bottom:80px;
  width:100%;
  display:block;
  clear:both;
}
/* Ensure our page is not constrained by a Elementor inner container */
.hf2-page.entry-content,
.hf2-page.product{
  max-width:100% !important;
  padding:0 !important;
}

/* ─── Announce bar (opacity fade) ─── */
.hf2-announce{
  background:var(--dark);
  color:#fff;
  text-align:center;
  padding:8px 16px;
  font-size:13px;
  font-weight:600;
  min-height:36px;
  position:relative;
  overflow:hidden;
}
.hf2-announce-inner{position:relative;height:20px;overflow:hidden}
.hf2-announce-slide{
  position:absolute;
  width:100%;
  text-align:center;
  top:0;
  opacity:0;
  transition:opacity .5s;
}
.hf2-announce-slide.visible{opacity:1}

/* ─── Jump nav ─── */
.hf2-jump-nav{
  background:var(--white);
  border-bottom:1px solid var(--border);
  padding:6px 12px;
  display:flex;
  justify-content:flex-end;
  gap:4px;
  overflow-x:auto;
  scrollbar-width:none;
  box-shadow:0 1px 6px rgba(0,0,0,.05);
}
.hf2-jump-nav::-webkit-scrollbar{display:none}
.hf2-jump-link{
  font-size:11px;font-weight:700;
  white-space:nowrap;padding:6px 13px;flex-shrink:0;
  cursor:pointer;border-right:none;
  text-decoration:none;color:var(--mid);
  border-radius:20px;
  transition:background .15s,color .15s;
}
.hf2-jump-link:hover{background:var(--cream);color:var(--dark)}
.hf2-jump-link.hf2-nav-active{background:var(--yellow);color:var(--dark)}

/* ─── Gallery ─── */
.hf2-gallery-wrap{
  background:var(--white);
  padding:16px 16px 0;
}
.hf2-gallery-main{
  position:relative;
  width:100%;
  aspect-ratio:1/1;
  border-radius:12px;
  overflow:hidden;
  background:#F0EDE5;
  box-shadow:var(--elev);
}
.hf2-gallery-main img{
  width:100%;height:100%;object-fit:cover;display:block;
  transition:opacity .25s;
}
.hf2-badge-israel{
  position:absolute;top:12px;right:12px;
  background:var(--white);
  border:1.5px solid #003399;
  border-radius:var(--r-sm);
  padding:4px 8px;
  font-size:10px;font-weight:800;
  color:#002299;
  display:flex;align-items:center;gap:4px;
  box-shadow:0 2px 6px rgba(0,0,0,.14);
  z-index:2;
}
.hf2-badge-video{
  position:absolute;bottom:12px;left:12px;
  background:rgba(0,0,0,.62);
  color:#fff;border-radius:20px;padding:5px 11px;
  font-size:11px;font-weight:600;cursor:pointer;z-index:2;
}
.hf2-thumbs{
  display:flex;gap:8px;padding:10px 0 12px;
  overflow-x:auto;scrollbar-width:none;
  background:var(--white);
}
.hf2-thumbs::-webkit-scrollbar{display:none}
.hf2-thumb{
  flex:0 0 62px;height:62px;
  border-radius:var(--r-sm);overflow:hidden;
  border:2px solid transparent;
  cursor:pointer;background:#F0EDE5;
  transition:border-color .2s;
}
.hf2-thumb.active,.hf2-thumb:hover{border-color:var(--yellow)}
.hf2-thumb img{width:100%;height:100%;object-fit:cover;display:block}

/* ─── Product info ─── */
.hf2-info{
  background:var(--white);
  padding:18px 16px 24px;
  display:flex;flex-direction:column;gap:14px;
}
.hf2-breadcrumb{font-size:11px;color:var(--hint)}
.hf2-breadcrumb a{color:var(--hint);text-decoration:none}
.hf2-info h1{font-size:22px;font-weight:900;line-height:1.3}
.hf2-rating-row{display:flex;align-items:center;gap:6px}
.hf2-stars{color:#F2B705;font-size:15px;letter-spacing:1px}
.hf2-rating-cnt{font-size:12px;color:var(--hint)}
.hf2-subtitle{
  font-size:15px;font-weight:700;line-height:1.55;
  border-right:3px solid var(--yellow);
  padding-right:10px;padding-top:2px;padding-bottom:2px;
}
.hf2-desc-short{font-size:13px;color:var(--mid);line-height:1.75;display:block}
.hf2-desc-full{
  font-size:13px;color:var(--mid);line-height:1.85;
  max-height:0;overflow:hidden;transition:max-height .5s ease;
}
.hf2-desc-full.open{max-height:900px}
.hf2-desc-full br+br{display:block;margin-top:6px}
.hf2-read-more{
  background:none;border:none;
  color:var(--hint);font-size:12px;font-weight:600;cursor:pointer;
  padding:4px 0;margin-top:2px;
  font-family:'Assistant',sans-serif;
  display:inline-flex;align-items:center;gap:4px;
  text-decoration:underline;text-underline-offset:3px;
  transition:color .15s;
}
.hf2-read-more:hover{color:var(--mid)}
.hf2-read-more-arrow{transition:transform .3s;display:inline-block;font-size:9px}
.hf2-read-more.open .hf2-read-more-arrow{transform:rotate(180deg)}
.hf2-trust-row{
  display:flex;gap:0;margin-top:6px;
  background:var(--cream);border:1px solid var(--border);
  border-radius:var(--r-md);overflow:hidden;
}
.hf2-trust-item{
  display:flex;align-items:center;gap:7px;flex:1;
  padding:10px 10px;
  border-right:1px solid var(--border);
}
.hf2-trust-item:last-child{border-right:none}
.hf2-trust-icon{font-size:18px;flex-shrink:0}
.hf2-trust-icon-img{width:28px!important;height:28px!important;object-fit:contain;flex-shrink:0}
.hf2-trust-label{font-size:11px;font-weight:700;color:var(--dark);line-height:1.3;text-align:right}

/* ─── Sizes section ─── */
.hf2-sizes-section{background:var(--white);margin-top:8px;padding-top:0}

/* Accordion container */
.hf2-sg-accordion{
  border-top:1px solid var(--border);
}
.hf2-sg-item{
  border-bottom:1px solid var(--border);
}

/* Trigger row */
.hf2-sg-tab{
  display:flex;flex-direction:row;align-items:center;
  width:100%;padding:12px 14px;gap:12px;
  background:var(--white);border:none;cursor:pointer;
  font-family:'Assistant',sans-serif;
  -webkit-tap-highlight-color:transparent;
  cursor:pointer;position:relative;
  border-left:1px solid var(--border);
  transition:background .15s;
  -webkit-tap-highlight-color:transparent;
}
.hf2-sg-tab:hover{background:var(--cream)}
.hf2-sg-item.active>.hf2-sg-tab{background:var(--yellow-lt)}
.hf2-sg-item.active>.hf2-sg-tab::after{
  content:'';position:absolute;
  top:0;bottom:0;right:0;
  width:3px;background:var(--yellow);z-index:1;
}
.hf2-sg-imgwrap{
  position:relative;display:block;margin-bottom:4px;
}
.hf2-sg-img{
  width:64px!important;height:64px!important;
  object-fit:cover;border-radius:8px;background:#F0EDE5;
  border:2px solid transparent;
  transition:border-color .15s;flex-shrink:0;display:block;
}
.hf2-sg-item.active .hf2-sg-img{border-color:var(--yellow)}

/* Chevron */
.hf2-sg-chevron{font-size:10px;color:var(--hint);margin-right:auto;transition:transform .25s;flex-shrink:0}
.hf2-sg-item.active>.hf2-sg-tab .hf2-sg-chevron{transform:rotate(180deg);color:var(--dark)}
.hf2-sg-tab-qty{margin-right:auto}

/* Panel slide */
.hf2-sg-panel{overflow:hidden;max-height:0;transition:max-height .35s ease}
.hf2-sg-item.active>.hf2-sg-panel{max-height:3000px}
.hf2-sg-panel-inner{padding:14px 14px 24px;background:var(--cream)}
.hf2-sg-panel-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid var(--border)}
.hf2-sg-count{
  position:absolute;top:3px;left:3px;
  background:rgba(0,0,0,.72);color:#fff;
  font-size:9px;font-weight:900;
  border-radius:3px;padding:1px 5px;
  line-height:1.4;pointer-events:none;
  letter-spacing:.02em;
}
.hf2-sg-tab-text{display:flex;flex-direction:column;gap:2px;text-align:right;flex:1}
.hf2-sg-label{font-size:15px;font-weight:800;color:var(--dark)}
.hf2-sg-dim-lbl{font-size:11px;color:var(--hint);text-align:right}


/* Panel head */
.hf2-sg-panel-head{
  display:flex;align-items:center;gap:8px;flex-wrap:wrap;
  margin-bottom:14px;padding-bottom:12px;
  border-bottom:1px solid var(--border);
}
.hf2-sg-panel-size{font-size:20px;font-weight:900;color:var(--dark)}
.hf2-sg-panel-dim{font-size:12px;color:var(--hint)}
.hf2-sg-panel-count{
  font-size:12px;font-weight:700;color:var(--dark);
  background:var(--yellow);border-radius:20px;
  padding:2px 9px;
}
.hf2-sg-panel-note{
  font-size:10px;color:var(--hint);font-style:italic;
  width:100%;margin-top:2px;
}

/* Use case rows (sg-row style from REDESIGN) — stacked on mobile */
.hf2-sg-rows{display:flex;flex-direction:column;gap:8px;margin-bottom:12px}
.hf2-sg-row{
  display:flex;flex-direction:column;gap:0;
  background:var(--white);border:1px solid var(--border);
  border-radius:var(--r-md);overflow:hidden;
}
.hf2-sg-row-img{
  width:100%!important;height:160px!important;
  object-fit:cover;display:block;
  cursor:zoom-in;border-radius:0;
}
.hf2-sg-row-body{flex:1;min-width:0;padding:10px}
.hf2-sg-row-title{font-size:14px;font-weight:800;color:var(--dark);margin-bottom:3px}
.hf2-sg-row-text{font-size:12px;color:var(--mid);line-height:1.6;margin-bottom:6px}

/* Solve box */
.hf2-sg-solve{
  background:var(--yellow-lt);
  padding:10px 12px;border-radius:var(--r-md);
  border:1px solid #E8D060;margin-top:4px;
}
.hf2-sg-solve-lbl{
  font-size:10px;font-weight:700;text-transform:uppercase;
  letter-spacing:.06em;color:#C99200;margin-bottom:3px;
}
.hf2-sg-solve-text{font-size:12px;color:var(--dark);line-height:1.6}

/* ─── Size panel media gallery strip ─── */
.hf2-sz-media{
  display:flex;gap:8px;overflow-x:auto;scrollbar-width:none;
  -webkit-overflow-scrolling:touch;
  margin-bottom:14px;padding-bottom:2px;
}
.hf2-sz-media::-webkit-scrollbar{display:none}
.hf2-sz-media-item{
  position:relative;flex:0 0 92px;height:92px;
  border-radius:var(--r-md);overflow:hidden;cursor:pointer;
  border:2px solid var(--border);background:#EDE8D8;
  transition:border-color .2s,box-shadow .2s;flex-shrink:0;
}
.hf2-sz-media-item:hover{border-color:var(--yellow);box-shadow:0 2px 8px rgba(0,0,0,.12)}
.hf2-sz-media-item img{
  width:100%!important;height:100%!important;
  object-fit:cover;display:block;
}
.hf2-sz-media-zoom{
  position:absolute;top:5px;right:5px;
  width:20px;height:20px;border-radius:50%;
  background:rgba(255,255,255,.88);
  display:flex;align-items:center;justify-content:center;
  font-size:10px;color:var(--dark);pointer-events:none;
}

/* ─── Size section title ─── */
.hf2-sg-title{
  font-size:16px;font-weight:900;color:var(--dark);
  padding:18px 16px 14px;
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:10px;
}
.hf2-sg-title::before{
  content:'';width:4px;height:18px;
  background:var(--yellow);border-radius:2px;
  display:inline-block;flex-shrink:0;
}

/* ─── Price + Cart ─── */
.hf2-cart-section{
  background:var(--white);
  padding:22px 16px;margin-top:8px;
  border-top:3px solid var(--yellow);
  box-shadow:var(--elev);
  position:relative;z-index:1;
}
.hf2-price-row{
  display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;
  margin-bottom:14px;
}
.hf2-cart-section .price,
.hf2-cart-section .woocommerce-Price-amount{
  font-size:30px!important;font-weight:900!important;color:var(--dark)!important;
}
.hf2-cart-section .price del,
.hf2-cart-section .price del .woocommerce-Price-amount{
  font-size:16px!important;color:var(--hint)!important;font-weight:500!important;
}
.hf2-price-save{
  background:var(--savings-bg);color:var(--savings);
  font-size:12px;font-weight:800;padding:3px 9px;border-radius:20px;
}
/* WC qty input */
.hf2-cart-section .quantity .qty{
  width:60px;text-align:center;border:1px solid var(--border);
  border-radius:6px;padding:8px;font-size:16px;font-family:'Assistant',sans-serif;
}
/* WC add to cart button */
.hf2-cart-section .single_add_to_cart_button,
.hf2-cart-section button.button[type=submit]{
  width:100%!important;padding:16px!important;
  background:var(--yellow)!important;
  border:none!important;border-radius:var(--r-md)!important;
  font-family:'Assistant',sans-serif!important;
  font-size:17px!important;font-weight:900!important;
  color:var(--dark)!important;cursor:pointer!important;
  margin-top:12px!important;transition:filter .2s!important;
  display:flex!important;align-items:center!important;justify-content:center!important;gap:8px!important;
}
.hf2-cart-section .single_add_to_cart_button:hover{filter:brightness(1.07)!important}
.hf2-shipping-trust{
  display:flex;justify-content:center;gap:0;flex-wrap:nowrap;
  font-size:11px;color:var(--mid);margin-top:16px;
  background:var(--cream);border:1px solid var(--border);
  border-radius:var(--r-md);overflow:hidden;
}
.hf2-shipping-trust span{
  display:flex;align-items:center;gap:4px;
  flex:1;justify-content:center;padding:9px 6px;
  border-right:1px solid var(--border);font-weight:600;
}
.hf2-shipping-trust span:last-child{border-right:none}

/* ─── Benefits bar ─── */
.hf2-benefits{background:var(--yellow);margin-top:8px}
.hf2-benefits-stripe{padding:20px 16px}
.hf2-benefits-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.hf2-benefit{
  display:flex;align-items:center;gap:10px;
  cursor:pointer;padding:6px 8px;border-radius:10px;
  transition:background .15s;-webkit-tap-highlight-color:transparent;
}
.hf2-benefit:hover{background:rgba(255,255,255,.35)}
.hf2-benefit.active{background:rgba(255,255,255,.55)}
.hf2-ben-icon{
  width:50px;height:50px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  background:rgba(255,255,255,.65);border-radius:12px;
  font-size:24px;
}
.hf2-ben-icon img{max-width:34px;max-height:34px;object-fit:contain}
.hf2-ben-text{flex:1;min-width:0}
.hf2-ben-title{font-size:13px;font-weight:800;color:var(--dark)}
.hf2-ben-sub{font-size:11px;color:#444;margin-top:1px}
.hf2-ben-more{
  margin-inline-start:auto;font-size:20px;font-weight:800;
  color:rgba(0,0,0,.45);transition:transform .2s;flex-shrink:0;
}
.hf2-benefit.active .hf2-ben-more{transform:rotate(45deg)}
.hf2-ben-detail{overflow:hidden;max-height:0;transition:max-height .3s ease}
.hf2-ben-detail.open{max-height:360px}
.hf2-ben-detail-panel{display:none;padding:0 16px 16px}
.hf2-ben-detail-panel.active{display:block}
.hf2-ben-detail-panel p{
  background:var(--white);border-radius:var(--r-md);
  padding:14px 16px;font-size:13px;line-height:1.7;color:var(--dark);margin:0;
}

/* ─── Stock + shipping indicator ─── */
.hf2-stock-row{display:flex;align-items:center;gap:16px;margin-bottom:14px;font-size:13px}
.hf2-stock-ok{color:#2D6E3E;font-weight:700;display:flex;align-items:center;gap:4px}
.hf2-stock-ok::before{content:'●';font-size:10px}
.hf2-ship-eta{color:var(--mid)}

/* ─── Accordion (specs / care) ─── */
.hf2-accordion-sec{background:var(--white);padding:0 16px;margin-top:8px}
.hf2-acc-item{border-top:1px solid var(--border)}
.hf2-acc-item:last-child{border-bottom:1px solid var(--border)}
.hf2-acc-trigger{
  width:100%;display:flex;align-items:center;justify-content:space-between;
  padding:14px 0;background:none;border:none;
  font-family:'Assistant',sans-serif;font-size:14px;font-weight:700;
  color:var(--dark);cursor:pointer;text-align:right;gap:8px;
}
.hf2-acc-icon{font-size:16px;flex-shrink:0}
.hf2-acc-title{flex:1;text-align:right}
.hf2-acc-chevron{
  width:22px;height:22px;border-radius:50%;
  background:var(--cream);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  font-size:9px;flex-shrink:0;
  transition:transform .25s,background .25s,border-color .25s;
}
.hf2-acc-item.open .hf2-acc-chevron{transform:rotate(180deg);background:var(--yellow);border-color:var(--yellow)}
.hf2-acc-body{
  font-size:13px;color:var(--mid);line-height:1.75;
  max-height:0;overflow:hidden;transition:max-height .45s ease,padding .3s;
}
.hf2-acc-item.open .hf2-acc-body{max-height:800px;padding-bottom:14px}
/* video inside accordion toggle */
.hf2-acc-video{
  position:relative;width:100%;padding-top:56.25%;
  background:#111;border-radius:10px;overflow:hidden;cursor:pointer;
  margin-top:12px;
}
.hf2-acc-video iframe,
.hf2-acc-video video{
  position:absolute;inset:0;width:100%;height:100%;border:0;
}
.hf2-acc-video-play{
  position:absolute;inset:0;
  display:flex;align-items:center;justify-content:center;
  background:rgba(0,0,0,.30);
}
.hf2-acc-video-play-btn{
  width:60px;height:60px;border-radius:50%;
  background:var(--yellow);
  display:flex;align-items:center;justify-content:center;
  font-size:24px;color:var(--dark);
  box-shadow:0 4px 16px rgba(0,0,0,.3);
  transition:transform .2s;border:none;cursor:pointer;pointer-events:none;
}
.hf2-acc-video:hover .hf2-acc-video-play-btn{transform:scale(1.1)}

/* ─── Videos section ─── */
.hf2-videos{
  background:var(--cream);
  padding:24px 16px;margin-top:8px;
  border-top:1px solid var(--border);
}
.hf2-section-title{
  font-size:20px;font-weight:900;color:var(--dark);
  margin-bottom:18px;
  display:flex;align-items:center;gap:10px;
}
.hf2-section-title::before{
  content:'';width:4px;height:20px;
  background:var(--yellow);border-radius:2px;
  display:inline-block;flex-shrink:0;
}
.hf2-videos-grid{display:flex;flex-direction:column;gap:14px}
.hf2-video-item{
  background:var(--white);
  border:1px solid var(--border);
  border-radius:var(--r-lg);
  overflow:hidden;
  transition:box-shadow .2s,transform .2s;
}
.hf2-video-item:hover{box-shadow:0 6px 20px rgba(0,0,0,.1);transform:translateY(-2px)}
.hf2-video-container{
  position:relative;width:100%;padding-top:56.25%;
  background:#111;border-radius:0;overflow:hidden;cursor:pointer;
}
.hf2-video-container iframe,
.hf2-video-container video{
  position:absolute;inset:0;width:100%;height:100%;border:0;
}
.hf2-video-thumb{position:absolute;inset:0}
.hf2-video-thumb img{width:100%;height:100%;object-fit:cover;display:block}
.hf2-video-thumb-placeholder{position:absolute;inset:0;background:#222}
.hf2-video-play{
  position:absolute;inset:0;
  display:flex;align-items:center;justify-content:center;
  background:rgba(0,0,0,.22);
}
.hf2-video-play-btn{
  width:64px;height:64px;border-radius:50%;
  background:var(--yellow);
  display:flex;align-items:center;justify-content:center;
  font-size:26px;color:var(--dark);
  box-shadow:0 4px 16px rgba(0,0,0,.3);
  transition:transform .2s;border:none;cursor:pointer;
}
.hf2-video-container:hover .hf2-video-play-btn{transform:scale(1.1)}
.hf2-video-title{font-size:14px;font-weight:700;margin-top:0;padding:12px 14px 4px}
.hf2-video-desc{font-size:12px;color:var(--gray);margin-top:0;line-height:1.6;padding:0 14px 14px}

/* ─── Size quality panel (replaces media gallery) ─── */
.hf2-sg-quality{
  display:flex;gap:14px;align-items:flex-start;
  padding:12px 16px;
  background:var(--white);border:1px solid var(--border);
  border-radius:var(--r-md);margin:0 16px 10px;
}
.hf2-sg-quality-img{
  width:72px;height:72px;flex-shrink:0;
  object-fit:cover;border-radius:8px;background:#EDE8D8;
}
.hf2-sg-quality-body{flex:1;min-width:0}
.hf2-sg-solve-inline{
  display:flex;align-items:baseline;gap:6px;
  margin-top:8px;padding-top:8px;
  border-top:1px solid var(--border);
  flex-wrap:wrap;
}
.hf2-sg-solve-inline .hf2-sg-solve-lbl{
  font-size:10px;font-weight:700;text-transform:uppercase;
  letter-spacing:.06em;color:#C99200;flex-shrink:0;
}
.hf2-sg-solve-inline .hf2-sg-solve-text{
  font-size:12px;color:var(--mid);line-height:1.5;
}

/* ─── FAQ accordion ─── */
.hf2-faq{background:var(--white);padding:22px 16px;margin-top:8px;border-top:1px solid var(--border)}
.hf2-faq-title{
  font-size:20px;font-weight:900;color:var(--dark);
  margin-bottom:18px;
  display:flex;align-items:center;gap:10px;
}
.hf2-faq-title::before{
  content:'';width:4px;height:20px;
  background:var(--yellow);border-radius:2px;
  display:inline-block;flex-shrink:0;
}
.hf2-faq-subtitle{font-size:13px;color:var(--hint);margin-bottom:14px;margin-top:-14px}
.hf2-faq-item{border-top:1px solid var(--border)}
.hf2-faq-item:last-child{border-bottom:1px solid var(--border)}
.hf2-faq-trigger{
  width:100%;display:flex;justify-content:space-between;align-items:center;
  padding:14px 0;background:none;border:none;
  font-family:'Assistant',sans-serif;font-size:14px;font-weight:700;
  color:var(--dark);cursor:pointer;text-align:right;gap:8px;
}
/* circular chevron */
.hf2-faq-chevron{
  width:22px;height:22px;border-radius:50%;
  background:var(--cream);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  font-size:9px;flex-shrink:0;
  transition:transform .25s,background .25s,border-color .25s;
}
.hf2-faq-item.open .hf2-faq-chevron{
  transform:rotate(180deg);
  background:var(--yellow);border-color:var(--yellow);
}
.hf2-faq-body{
  font-size:13px;color:var(--mid);line-height:1.75;
  max-height:0;overflow:hidden;transition:max-height .3s ease,padding .3s;
}
.hf2-faq-item.open .hf2-faq-body{max-height:600px;padding-bottom:14px}

/* ─── How to use (vertical steps) ─── */
.hf2-howto{background:var(--white);padding:22px 16px;margin-top:8px}
.hf2-steps{display:flex;flex-direction:column;gap:0;margin-top:14px}
.hf2-step{display:flex;gap:14px;align-items:flex-start;position:relative;padding-bottom:18px}
.hf2-step:last-child{padding-bottom:0}
.hf2-step-num{
  min-width:34px;height:34px;background:var(--yellow);
  border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:14px;font-weight:900;flex-shrink:0;
  position:relative;z-index:1;
  box-shadow:0 0 0 4px rgba(242,183,5,.18);
}
.hf2-step:not(:last-child) .hf2-step-num::after{
  content:'';position:absolute;
  top:34px;right:50%;transform:translateX(50%);
  width:2px;height:calc(100% + 18px - 34px);
  background:var(--border);z-index:0;
}
.hf2-step-body{flex:1;padding-top:5px}
.hf2-step-label{font-size:14px;font-weight:800;color:var(--dark)}
.hf2-step-sub{font-size:12px;color:var(--mid);margin-top:3px;line-height:1.55}

/* ─── Related products ─── */
.hf2-related{padding:20px 16px;margin-top:8px;background:var(--yellow-lt)}
.hf2-related .related.products h2{
  font-size:18px;font-weight:900;margin-bottom:16px;
  font-family:'Assistant',sans-serif;color:var(--dark);
}
.hf2-related ul.products{
  display:flex;overflow-x:auto;gap:12px;
  margin:0;padding:0 0 6px;list-style:none;
  scrollbar-width:none;-webkit-overflow-scrolling:touch;
}
.hf2-related ul.products::-webkit-scrollbar{display:none}
.hf2-related ul.products li.product{
  flex:0 0 152px !important;margin:0 !important;width:152px !important;
  float:none !important;
  background:var(--white);border-radius:var(--r-md);
  overflow:hidden;border:1px solid var(--border);
}
.hf2-related ul.products li.product a img{
  width:100% !important;height:110px;object-fit:cover;display:block;
  border-radius:0;
}
.hf2-related ul.products .woocommerce-loop-product__title{
  font-size:12px;font-weight:700;margin:7px 8px 2px;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.hf2-related ul.products .price{font-size:13px;font-weight:900;margin:0 8px 8px;display:block}
.hf2-related ul.products .button{display:none}

/* ─── Lightbox ─── */
.hf2-lightbox{
  position:fixed;inset:0;z-index:99999;
  background:rgba(20,18,12,.88);
  display:flex;align-items:center;justify-content:center;
  display:none;padding:24px;
}
.hf2-lightbox.open{display:flex}
.hf2-lightbox-inner{
  position:relative;max-width:92vw;max-height:92vh;
  display:flex;align-items:center;justify-content:center;
}
.hf2-lightbox-inner img{max-width:90vw;max-height:78vh;border-radius:8px;display:block;background:#000}
.hf2-lightbox-inner video{max-width:90vw;max-height:78vh;border-radius:8px;display:block}
.hf2-lightbox-inner iframe{width:80vw;height:45vw;max-width:960px;max-height:540px;border:0;border-radius:8px}
.hf2-lightbox-close{
  position:absolute;top:16px;left:16px;
  width:42px;height:42px;border-radius:50%;
  background:rgba(255,255,255,.92);border:none;
  font-size:22px;line-height:1;cursor:pointer;color:var(--dark);
  display:flex;align-items:center;justify-content:center;
}

/* ─── Sticky buy bar (mobile only) ─── */
.hf2-sticky{
  position:fixed;bottom:0;left:0;right:0;z-index:9999;
  background:rgba(255,255,255,.88);
  backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
  border-top:2px solid var(--yellow);
  padding:12px 16px 18px;
  display:flex;align-items:center;gap:10px;
  box-shadow:0 -4px 20px rgba(0,0,0,.12);
  opacity:1;transition:opacity .3s,transform .3s;
}
.hf2-sticky.hidden{opacity:0;pointer-events:none;transform:translateY(100%)}
.hf2-sticky-price{flex-shrink:0;display:flex;align-items:baseline;gap:6px}
.hf2-sticky-price-current{font-size:20px;font-weight:900;color:var(--dark)}
.hf2-sticky-price-orig{font-size:13px;color:var(--hint);text-decoration:line-through}
.hf2-sticky-btn{
  flex:1;padding:12px 0;
  background:var(--yellow);border:none;border-radius:var(--r-md);
  font-family:'Assistant',sans-serif;font-size:14px;font-weight:900;
  color:var(--dark);cursor:pointer;transition:filter .2s;
}
.hf2-sticky-btn:hover{filter:brightness(1.08)}

/* ─── Desktop ≥800px ─── */
@media(min-width:800px){
  .hf2-sticky{display:none}
  .hf2-page{padding-bottom:0}

  /* ── Announce ── */
  .hf2-announce{font-size:14px}

  /* ── Hero: 2-col, 1200px max, gallery 4:3 ── */
  .hf2-hero-desktop{
    display:grid !important;
    grid-template-columns:55fr 45fr;
    gap:48px;
    align-items:start;
    background:var(--white);
    max-width:1200px;
    margin:0 auto;
    padding:40px;
  }
  .hf2-gallery-wrap{padding:0;background:transparent}
  .hf2-gallery-main{aspect-ratio:4/3}
  .hf2-thumbs{padding:12px 0 0}
  .hf2-info{padding:4px 0 0;background:transparent}
  .hf2-info h1{font-size:28px;line-height:1.25}
  .hf2-subtitle{font-size:16px}
  .hf2-read-more{max-width:320px}

  /* ── Sizes desktop: accordion HTML → tab UI via display:contents ── */
  .hf2-sizes-section{max-width:1200px;margin:8px auto 0;padding:0;background:var(--white)}
  .hf2-sg-title{padding:24px 40px 16px;font-size:16px;font-weight:800;border-bottom:1px solid var(--border)}

  /* Flatten accordion into tab strip + panels */
  .hf2-sg-accordion{
    display:flex;flex-wrap:wrap;
    border-top:none;
    border-bottom:1.5px solid var(--border);
    padding:0 40px;
  }
  .hf2-sg-item{
    display:contents; /* ghost — children join parent flex directly */
  }
  /* All tabs appear first (order:0 default), all panels after (order:1) */
  .hf2-sg-tab{
    order:0;
    flex:0 0 auto;
    flex-direction:column;
    align-items:center;
    padding:16px 18px 14px;
    width:auto;
    border-bottom:none;
    border-left:1px solid var(--border);
    border-right:none;
    gap:6px;
  }
  .hf2-sg-tab-text{text-align:center}
  .hf2-sg-dim-lbl{text-align:center}
  .hf2-sg-label{font-size:14px}
  .hf2-sg-img{width:58px!important;height:58px!important}
  .hf2-sg-chevron{display:none}
  .hf2-sg-tab-qty{display:none}

  /* Active tab indicator: bottom bar */
  .hf2-sg-item.active>.hf2-sg-tab::after{
    top:auto;bottom:-1.5px;left:0;right:0;
    width:auto;height:3px;
  }

  /* Panels: full width, instant switch — no transition to avoid jitter */
  .hf2-sg-panel{order:1;flex:0 0 100%;max-height:none;overflow:visible;transition:none;display:none}
  .hf2-sg-item.active>.hf2-sg-panel{display:block}
  .hf2-sg-panel-inner{padding:28px 40px 40px}

  /* Use-case rows: side-by-side on desktop */
  .hf2-sg-row{flex-direction:row;align-items:flex-start;padding:14px;gap:14px}
  .hf2-sg-row-body{padding:0}
  .hf2-sg-row-img{width:80px!important;height:80px!important;border-radius:6px;flex-shrink:0}
  .hf2-sg-row-title{font-size:15px}
  .hf2-sg-row-text{font-size:13px}

  /* ── Cart ── */
  .hf2-cart-section{
    max-width:1200px;margin:8px auto 0;
    padding:32px 40px;
    box-shadow:0 2px 12px rgba(0,0,0,.05);
  }
  .hf2-price-row{margin-bottom:20px}

  /* ── Accordion ── */
  .hf2-accordion-sec{max-width:1200px;margin:0 auto;padding:0 40px}

  /* ── Benefits: 4-col single row ── */
  .hf2-benefits-stripe{
    max-width:1200px;margin:0 auto;
    padding:24px 40px;
  }
  .hf2-benefits-row{grid-template-columns:repeat(4,1fr);gap:8px}
  .hf2-benefit{flex-direction:column;align-items:center;text-align:center;padding:12px 8px}
  .hf2-ben-text{text-align:center}
  .hf2-ben-more{display:none}
  .hf2-ben-detail-panel p{text-align:right}

  /* ── Videos: 3-col cards ── */
  .hf2-videos{
    background:var(--cream);
    max-width:1200px;margin:8px auto 0;padding:32px 40px;
  }
  .hf2-videos .hf2-section-title{font-size:22px}
  .hf2-videos-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
  }
  .hf2-video-item{transition:box-shadow .2s,transform .2s}
  .hf2-video-item:hover{box-shadow:0 8px 28px rgba(0,0,0,.12);transform:translateY(-3px)}

  /* ── FAQ ── */
  .hf2-faq{max-width:1200px;margin:8px auto 0;padding:32px 40px}
  .hf2-faq-title{font-size:22px}

  /* ── How to: horizontal ── */
  .hf2-howto{max-width:1200px;margin:8px auto 0;padding:32px 40px}
  .hf2-howto .hf2-section-title{font-size:22px}
  .hf2-steps{flex-direction:row;gap:24px}
  .hf2-step{flex:1;min-width:140px;flex-direction:column;align-items:flex-start}
  .hf2-step-num{width:44px;height:44px;font-size:16px;margin-bottom:8px}
  .hf2-step-label{font-size:15px}

  /* ── Related: 4-col grid ── */
  .hf2-related{max-width:1200px;margin:8px auto 0;padding:32px 40px}
  .hf2-related .related.products h2{font-size:22px}
  .hf2-related ul.products{
    display:grid !important;
    grid-template-columns:repeat(4,1fr) !important;
    gap:16px;
    overflow:visible;
  }
  .hf2-related ul.products li.product{
    flex:none !important;width:auto !important;float:none !important;
  }
  .hf2-related ul.products li.product a img{height:160px !important}
  .hf2-related ul.products .woocommerce-loop-product__title{white-space:normal}
  /* FAQ desktop */
  .hf2-faq{border-top:none;border:1px solid var(--border)}
}
</style>

<!-- ═══════════════════════════════════════ MARKUP ═══════════ -->
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'hf2-page', $product ); ?>>
<?php do_action( 'woocommerce_before_single_product' ); ?>

<!-- ── 0. ANNOUNCE BAR ─────────────────────────────────────── -->
<div class="hf2-announce" aria-live="polite">
  <div class="hf2-announce-inner" id="hf2-announce-inner">
    <?php foreach ( $announce as $i => $msg ) : ?>
    <div class="hf2-announce-slide<?php echo $i === 0 ? ' visible' : ''; ?>">
      <?php echo esc_html( $msg ); ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── 1. JUMP NAV ────────────────────────────────────────── -->
<div class="hf2-jump-nav">
  <?php if ( $sizes ) : ?>
  <a class="hf2-jump-link" href="#hf2-sizes-section">גדלים</a>
  <?php endif; ?>
  <a class="hf2-jump-link" href="#hf2-cart-section">מחיר</a>
  <?php if ( $bens ) : ?>
  <a class="hf2-jump-link" href="#hf2-benefits">יתרונות</a>
  <?php endif; ?>
  <?php if ( $faq ) : ?>
  <a class="hf2-jump-link" href="#hf2-faq">שאלות</a>
  <?php endif; ?>
</div>

<div class="hf2-hero-desktop">

<!-- ── 2. GALLERY ───────────────────────────────────────────── -->
<div class="hf2-gallery-wrap">
  <div class="hf2-gallery-main" id="hf2-gallery-main">
    <?php
    if ( $all_ids ) :
      $main_src = wp_get_attachment_image_url( $all_ids[0], 'woocommerce_single' );
    else :
      $main_src = $brand_imgs[0];
    endif;
    ?>
    <img id="hf2-main-img"
         src="<?php echo esc_url( $main_src ); ?>"
         alt="<?php the_title_attribute(); ?>">
    <div id="hf2-size-caption" class="hf2-size-caption" style="display:none;position:absolute;bottom:0;right:0;left:0;background:rgba(0,0,0,.52);color:#fff;font-size:12px;font-weight:600;padding:6px 10px;text-align:right"></div>
    <div class="hf2-badge-israel">🇮🇱 ישראלי</div>
    <?php if ( $main_video ) : ?>
    <div class="hf2-badge-video" data-video="<?php echo esc_attr( $main_video ); ?>" id="hf2-gallery-video-badge">
      ▶ סרטון
    </div>
    <?php endif; ?>
  </div>

  <!-- Thumbnail strip -->
  <div class="hf2-thumbs">
    <?php if ( $all_ids ) :
      foreach ( $all_ids as $k => $img_id ) :
        $t_src = wp_get_attachment_image_url( $img_id, 'thumbnail' );
        $l_src = wp_get_attachment_image_url( $img_id, 'woocommerce_single' );
    ?>
    <div class="hf2-thumb<?php echo $k === 0 ? ' active' : ''; ?>"
         data-src="<?php echo esc_url( $l_src ); ?>"
         data-large="<?php echo esc_url( $l_src ); ?>">
      <img src="<?php echo esc_url( $t_src ); ?>"
           alt="<?php the_title_attribute(); ?> <?php echo $k + 1; ?>">
    </div>
    <?php endforeach;
    else :
      foreach ( $brand_imgs as $k => $bsrc ) : ?>
    <div class="hf2-thumb<?php echo $k === 0 ? ' active' : ''; ?>"
         data-src="<?php echo esc_url( $bsrc ); ?>">
      <img src="<?php echo esc_url( $bsrc ); ?>" alt="">
    </div>
    <?php endforeach;
    endif; ?>
  </div>
</div><!-- .hf2-gallery-wrap -->

<!-- ── 3. PRODUCT INFO ─────────────────────────────────────── -->
<div class="hf2-info">
  <p class="hf2-breadcrumb">
    <a href="<?php echo esc_url( home_url() ); ?>">בית</a> /
    <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">חנות</a> /
    <?php the_title(); ?>
  </p>

  <h1><?php the_title(); ?></h1>

  <!-- Rating -->
  <?php if ( $rv ) :
    $full_stars = floor( $rv );
    $half_star  = ( $rv - $full_stars ) >= 0.5;
  ?>
  <div class="hf2-rating-row">
    <span class="hf2-stars" aria-label="<?php echo esc_attr( $rv ); ?> כוכבים">
      <?php for ( $i = 0; $i < 5; $i++ ) {
        if ( $i < $full_stars )                    echo '★';
        elseif ( $i == $full_stars && $half_star ) echo '★';
        else                                       echo '☆';
      } ?>
    </span>
    <?php if ( $rc ) : ?>
    <span class="hf2-rating-cnt">(<?php echo esc_html( $rc ); ?> ביקורות)</span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Subtitle / sales hook -->
  <?php if ( $sub ) : ?>
  <p class="hf2-subtitle"><?php echo esc_html( $sub ); ?></p>
  <?php endif; ?>

  <!-- Short / full description -->
  <?php if ( $desc ) : ?>
  <span class="hf2-desc-short"><?php echo esc_html( $desc ); ?></span>
  <?php if ( $desc_full ) : ?>
  <div class="hf2-desc-full" id="hf2-desc-full"><?php echo nl2br( esc_html( $desc_full ) ); ?></div>
  <button class="hf2-read-more" id="hf2-read-more-btn" type="button">
    קראי עוד <span class="hf2-read-more-arrow">▼</span>
  </button>
  <?php endif; ?>
  <?php endif; ?>

  <!-- Trust badges -->
  <?php if ( $trust ) : ?>
  <div class="hf2-trust-row">
    <?php foreach ( $trust as $t ) : ?>
    <div class="hf2-trust-item">
      <?php if ( ! empty( $t['icon_url'] ) ) : ?>
      <img class="hf2-trust-icon-img" src="<?php echo esc_url( $t['icon_url'] ); ?>" alt="<?php echo esc_attr( $t['label'] ?? '' ); ?>">
      <?php else : ?>
      <span class="hf2-trust-icon"><?php echo esc_html( $t['icon'] ?? '' ); ?></span>
      <?php endif; ?>
      <span class="hf2-trust-label"><?php echo esc_html( $t['label'] ?? '' ); ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div><!-- .hf2-info -->

</div><!-- .hf2-hero-desktop -->

<!-- HF-TEMPLATE-V1.5-LOADED -->
<!-- ── 4. SIZES ─────────────────────────────────────────────── -->
<?php if ( $sizes ) : ?>
<section class="hf2-sizes-section" id="hf2-sizes-section">
  <div class="hf2-sg-title">הגדלים, השימושים והכמויות</div>

  <!-- Accordion: each size tab + panel paired -->
  <div class="hf2-sg-accordion">
    <?php foreach ( $sizes as $i => $sz ) :
      $lbl    = $sz['label']     ?? '';
      $dim    = $sz['dim']       ?? '';
      $thumb  = $sz['thumb_url'] ?? '';
      $count  = $sz['count']     ?? '';
      $qty    = $sz['qty']       ?? '';
      $note   = $sz['note']      ?? '';
      $solves = $sz['solves']    ?? '';
    ?>
    <div class="hf2-sg-item<?php echo $i === 0 ? ' active' : ''; ?>" data-sz="<?php echo $i; ?>">

      <!-- Trigger row -->
      <button class="hf2-sg-tab" type="button"
              aria-expanded="<?php echo $i === 0 ? 'true' : 'false'; ?>">
        <div class="hf2-sg-imgwrap">
          <?php if ( $thumb ) : ?>
          <img class="hf2-sg-img" src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $lbl ); ?>">
          <?php else : ?>
          <div class="hf2-sg-img"></div>
          <?php endif; ?>
          <?php if ( $count ) : ?>
          <span class="hf2-sg-count"><?php echo esc_html( $count ); ?></span>
          <?php endif; ?>
        </div>
        <div class="hf2-sg-tab-text">
          <span class="hf2-sg-label"><?php echo esc_html( $lbl ); ?></span>
          <?php if ( $dim ) : ?><span class="hf2-sg-dim-lbl"><?php echo esc_html( $dim ); ?></span><?php endif; ?>
        </div>
        <?php if ( $qty ) : ?>
        <span class="hf2-sztab-tag hf2-sg-tab-qty"><?php echo esc_html( $qty ); ?></span>
        <?php endif; ?>
        <span class="hf2-sg-chevron">▼</span>
      </button>

      <!-- Panel — slides open below its trigger -->
      <div class="hf2-sg-panel">
        <div class="hf2-sg-panel-inner">
          <?php if ( $count ) : ?>
          <div class="hf2-sg-panel-meta">
            <span class="hf2-sg-panel-count"><?php echo esc_html( $count ); ?> במארז</span>
            <?php if ( $note ) : ?>
            <span class="hf2-sg-panel-note">* <?php echo esc_html( $note ); ?></span>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Use case rows -->
          <?php if ( ! empty( $sz['uses'] ) ) : ?>
          <div class="hf2-sg-rows">
            <?php foreach ( $sz['uses'] as $uc ) : ?>
            <div class="hf2-sg-row">
              <?php if ( ! empty( $uc['img'] ) ) : ?>
              <img class="hf2-sg-row-img"
                   src="<?php echo esc_url( $uc['img'] ); ?>"
                   alt="<?php echo esc_attr( $uc['title'] ?? '' ); ?>"
                   onclick="openLightbox(<?php echo wp_json_encode( esc_url_raw( $uc['img'] ) ); ?>,'image')">
              <?php endif; ?>
              <div class="hf2-sg-row-body">
                <div class="hf2-sg-row-title"><?php echo esc_html( $uc['title'] ?? '' ); ?></div>
                <?php if ( ! empty( $uc['text'] ) ) : ?>
                <div class="hf2-sg-row-text"><?php echo nl2br( esc_html( $uc['text'] ) ); ?></div>
                <?php endif; ?>
                <?php if ( ! empty( $uc['saves'] ) ) : ?>
                <span class="hf2-use-case-saves"><?php echo esc_html( $uc['saves'] ); ?></span>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- Solve box -->
          <?php if ( $solves ) : ?>
          <div class="hf2-sg-solve">
            <div class="hf2-sg-solve-lbl">מה זה פותר?</div>
            <div class="hf2-sg-solve-text"><?php echo esc_html( $solves ); ?></div>
          </div>
          <?php endif; ?>
        </div><!-- .hf2-sg-panel-inner -->
      </div><!-- .hf2-sg-panel -->

    </div><!-- .hf2-sg-item -->
    <?php endforeach; ?>
  </div><!-- .hf2-sg-accordion -->

</section>
<?php endif; ?>

<!-- ── 5. PRICE + CART ──────────────────────────────────────── -->
<section class="hf2-cart-section" id="hf2-cart-section">
  <!-- Stock + shipping ETA -->
  <div class="hf2-stock-row">
    <?php if ( $product->is_in_stock() ) : ?>
    <span class="hf2-stock-ok">במלאי</span>
    <span class="hf2-ship-eta">📦 משלוח תוך 1-3 ימי עסקים</span>
    <?php else : ?>
    <span style="color:#B53A2A;font-weight:700">❌ אזל המלאי</span>
    <?php endif; ?>
  </div>
  <div class="hf2-price-row">
    <?php woocommerce_template_single_price(); ?>
    <?php if ( $product->is_on_sale() ) : ?>
    <span class="hf2-price-save">חסכי <?php echo wp_kses_post( wc_price( (float)$product->get_regular_price() - (float)$product->get_sale_price() ) ); ?></span>
    <?php endif; ?>
  </div>
  <?php woocommerce_template_single_add_to_cart(); ?>
  <div class="hf2-shipping-trust">
    <?php
    $trust_parts = explode( '|', $ship );
    foreach ( $trust_parts as $tp ) :
      $tp = trim( $tp );
      if ( $tp ) :
    ?>
    <span><?php echo esc_html( $tp ); ?></span>
    <?php endif; endforeach; ?>
  </div>
</section>


<!-- ── 5b. ACCORDION (specs / care) ───────────────────────── -->
<?php if ( $acc ) : ?>
<section class="hf2-accordion-sec">
  <?php foreach ( $acc as $ai => $a ) :
    $atitle   = $a['title']     ?? '';
    $abody    = $a['body']      ?? '';
    $aicon    = $a['icon']      ?? '';
    $avideo   = $a['video_url'] ?? '';
    $aopen    = ! empty( $a['open'] ) && $ai === 0;
  ?>
  <div class="hf2-acc-item<?php echo $aopen ? ' open' : ''; ?>">
    <button class="hf2-acc-trigger" type="button" aria-expanded="<?php echo $aopen ? 'true' : 'false'; ?>">
      <?php if ( $aicon ) : ?><span class="hf2-acc-icon"><?php echo esc_html( $aicon ); ?></span><?php endif; ?>
      <span class="hf2-acc-title"><?php echo esc_html( $atitle ); ?></span>
      <span class="hf2-acc-chevron">▼</span>
    </button>
    <div class="hf2-acc-body">
      <?php echo esc_html( $abody ); ?>
      <?php if ( $avideo ) : ?>
      <div class="hf2-acc-video" data-video-url="<?php echo esc_attr( $avideo ); ?>">
        <div class="hf2-acc-video-play">
          <button class="hf2-acc-video-play-btn" aria-label="נגן סרטון">▶</button>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<!-- ── 6. BENEFITS BAR ──────────────────────────────────────── -->
<?php if ( $bens ) : ?>
<section class="hf2-benefits" id="hf2-benefits">
  <div class="hf2-benefits-stripe">
    <div class="hf2-benefits-row">
      <?php foreach ( $bens as $bi => $b ) :
        $icon     = $b['icon']     ?? '';
        $icon_url = $b['icon_url'] ?? '';
        $title    = $b['title']    ?? '';
        $bsub     = $b['sub']      ?? $b['subtitle'] ?? '';
      ?>
      <div class="hf2-benefit" data-ben-idx="<?php echo $bi; ?>">
        <div class="hf2-ben-icon">
          <?php if ( $icon_url ) : ?>
          <img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $title ); ?>">
          <?php else : ?>
          <?php echo esc_html( $icon ); ?>
          <?php endif; ?>
        </div>
        <div class="hf2-ben-text">
          <div class="hf2-ben-title"><?php echo esc_html( $title ); ?></div>
          <div class="hf2-ben-sub"><?php echo esc_html( $bsub ); ?></div>
        </div>
        <span class="hf2-ben-more">+</span>
      </div>
      <?php endforeach; ?>
    </div>
    <!-- Single detail panel below all benefits -->
    <div class="hf2-ben-detail" id="hf2-ben-detail-all">
      <?php foreach ( $bens as $bi => $b ) :
        $bsub = $b['sub'] ?? $b['subtitle'] ?? '';
        $btitle = $b['title'] ?? '';
      ?>
      <div class="hf2-ben-detail-panel" id="hf2-bd-<?php echo $bi; ?>">
        <p><strong><?php echo esc_html( $btitle ); ?></strong><br><?php echo esc_html( $bsub ); ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── 7. VIDEOS (hidden per client — no video content yet) ── -->
<?php if ( false && $videos ) : ?>
<section class="hf2-videos">
  <h2 class="hf2-section-title">סרטונים</h2>
  <div class="hf2-videos-grid">
    <?php foreach ( $videos as $vi => $vid ) :
      $vtitle = $vid['title']     ?? '';
      $vurl   = $vid['url']       ?? '';
      $vthumb = $vid['thumb_url'] ?? '';
      $vdesc  = $vid['desc']      ?? '';
      if ( ! $vurl ) continue;
    ?>
    <div class="hf2-video-item">
      <div class="hf2-video-container" data-video-url="<?php echo esc_attr( $vurl ); ?>">
        <?php if ( $vthumb ) : ?>
        <div class="hf2-video-thumb">
          <img src="<?php echo esc_url( $vthumb ); ?>" alt="<?php echo esc_attr( $vtitle ); ?>">
        </div>
        <?php else : ?>
        <div class="hf2-video-thumb-placeholder"></div>
        <?php endif; ?>
        <div class="hf2-video-play">
          <button class="hf2-video-play-btn" aria-label="נגן סרטון">▶</button>
        </div>
      </div>
      <?php if ( $vtitle ) : ?>
      <div class="hf2-video-title"><?php echo esc_html( $vtitle ); ?></div>
      <?php endif; ?>
      <?php if ( $vdesc ) : ?>
      <div class="hf2-video-desc"><?php echo esc_html( $vdesc ); ?></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ── 8. FAQ ACCORDION ────────────────────────────────────── -->
<?php if ( $faq ) : ?>
<section class="hf2-faq" id="hf2-faq">
  <h2 class="hf2-faq-title">שאלות נפוצות</h2>
  <p class="hf2-faq-subtitle">מה העוטפת תפתור לך?</p>
  <?php foreach ( $faq as $fi => $f ) :
    $fq    = $f['q'] ?? $f['question'] ?? '';
    $fa    = $f['a'] ?? $f['answer']   ?? '';
    $fopen = $fi === 0;
  ?>
  <div class="hf2-faq-item<?php echo $fopen ? ' open' : ''; ?>">
    <button class="hf2-faq-trigger" type="button" aria-expanded="<?php echo $fopen ? 'true' : 'false'; ?>">
      <span><?php echo esc_html( $fq ); ?></span>
      <span class="hf2-faq-chevron">▼</span>
    </button>
    <div class="hf2-faq-body"><?php echo esc_html( $fa ); ?></div>
  </div>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<!-- ── 9. HOW TO USE (vertical steps) ─────────────────────── -->
<?php if ( $how ) : ?>
<section class="hf2-howto">
  <h2 class="hf2-section-title">איך משתמשים?</h2>
  <div class="hf2-steps">
    <?php foreach ( $how as $hi => $h ) : ?>
    <div class="hf2-step">
      <div class="hf2-step-num"><?php echo esc_html( $h['num'] ?? ( $hi + 1 ) ); ?></div>
      <div class="hf2-step-body">
        <div class="hf2-step-label"><?php echo esc_html( $h['label'] ?? '' ); ?></div>
        <?php if ( ! empty( $h['sub'] ) ) : ?>
        <div class="hf2-step-sub"><?php echo esc_html( $h['sub'] ); ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ── 10. RELATED PRODUCTS ──────────────────────────────── -->
<section class="hf2-related">
  <?php woocommerce_output_related_products(); ?>
</section>

<!-- ── 11. LIGHTBOX OVERLAY ───────────────────────────────── -->
<div class="hf2-lightbox" id="hf2-lightbox" role="dialog" aria-modal="true" aria-label="תצוגה מוגדלת">
  <div class="hf2-lightbox-inner" id="hf2-lightbox-inner">
    <button class="hf2-lightbox-close" id="hf2-lightbox-close" aria-label="סגור">&times;</button>
  </div>
</div>

<!-- ── 11. STICKY BUY BAR ────────────────────────────────── -->
<div class="hf2-sticky hidden" id="hf2-sticky">
  <div class="hf2-sticky-price">
    <span class="hf2-sticky-price-current">
      <?php echo wp_kses_post( $product->get_price_html() ); ?>
    </span>
    <?php if ( $product->is_on_sale() ) : ?>
    <span class="hf2-sticky-price-orig">
      <?php echo wp_kses_post( wc_price( $product->get_regular_price() ) ); ?>
    </span>
    <?php endif; ?>
  </div>
  <button class="hf2-sticky-btn" id="hf2-sticky-btn" type="button">הוסיפי לעגלה</button>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>

</div><!-- .hf2-page -->

<!-- ═══════════════════════════════════════ JAVASCRIPT ═══════ -->
<script>
(function () {
  'use strict';

  /* ── Announce bar — opacity fade ── */
  var slides = document.querySelectorAll('.hf2-announce-slide');
  if (slides.length > 1) {
    var cur = 0;
    setInterval(function () {
      slides[cur].classList.remove('visible');
      cur = (cur + 1) % slides.length;
      slides[cur].classList.add('visible');
    }, 3500);
  }

  /* ── Jump nav smooth scroll ── */
  document.querySelectorAll('.hf2-jump-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
      var href = link.getAttribute('href');
      if (href && href.charAt(0) === '#') {
        var target = document.querySelector(href);
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  });

  /* ── Gallery thumbnails ── */
  var mainImg = document.getElementById('hf2-main-img');
  document.querySelectorAll('.hf2-thumb').forEach(function (thumb) {
    thumb.addEventListener('click', function () {
      document.querySelectorAll('.hf2-thumb').forEach(function (t) { t.classList.remove('active'); });
      thumb.classList.add('active');
      if (mainImg && thumb.dataset.src) mainImg.src = thumb.dataset.src;
    });
  });

  /* ── Gallery video badge ── */
  var vidBadge = document.getElementById('hf2-gallery-video-badge');
  if (vidBadge) {
    vidBadge.addEventListener('click', function () {
      openLightbox(vidBadge.dataset.video, 'video');
    });
  }

  /* ── Size accordion ── */
  document.querySelectorAll('.hf2-sg-item .hf2-sg-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
      var item = tab.closest('.hf2-sg-item');
      var isActive = item.classList.contains('active');
      // close all
      document.querySelectorAll('.hf2-sg-item').forEach(function(el) {
        el.classList.remove('active');
        el.querySelector('.hf2-sg-tab').setAttribute('aria-expanded', 'false');
      });
      // open clicked (toggle off if already open)
      if (!isActive) {
        item.classList.add('active');
        tab.setAttribute('aria-expanded', 'true');
      }
    });
  });

  /* ── Jump nav scroll spy ── */
  (function(){
    var links = document.querySelectorAll('.hf2-jump-link[href^="#"]');
    if (!links.length) return;
    var sections = [];
    links.forEach(function(l){
      var id = l.getAttribute('href').replace('#','');
      var el = document.getElementById(id);
      if (el) sections.push({el:el,link:l});
    });
    function onScroll(){
      var scrollY = window.pageYOffset + 80;
      var active = sections[0];
      sections.forEach(function(s){
        if (s.el.offsetTop <= scrollY) active = s;
      });
      links.forEach(function(l){ l.classList.remove('hf2-nav-active'); });
      if (active) active.link.classList.add('hf2-nav-active');
    }
    window.addEventListener('scroll', onScroll, {passive:true});
    onScroll();
  })();

  /* ── Read more toggle ── */
  var rmBtn  = document.getElementById('hf2-read-more-btn');
  var rmFull = document.getElementById('hf2-desc-full');
  if (rmBtn && rmFull) {
    rmBtn.addEventListener('click', function () {
      var open = rmFull.classList.toggle('open');
      rmBtn.classList.toggle('open', open);
      var arrow = rmBtn.querySelector('.hf2-read-more-arrow');
      if (arrow) arrow.textContent = open ? '▲' : '▼';
      // update button text
      rmBtn.childNodes[0].textContent = open ? 'פחות ' : 'קראי עוד ';
    });
  }

  /* ── Benefits (single detail panel) ── */
  var lastBenIdx = null;
  var benDetailAll = document.getElementById('hf2-ben-detail-all');
  document.querySelectorAll('.hf2-benefit').forEach(function (ben) {
    ben.addEventListener('click', function () {
      var benIdx = ben.dataset.benIdx;
      var panel  = document.getElementById('hf2-bd-' + benIdx);

      document.querySelectorAll('.hf2-ben-detail-panel').forEach(function (p) { p.classList.remove('active'); });
      document.querySelectorAll('.hf2-benefit').forEach(function (b) { b.classList.remove('active'); });

      if (lastBenIdx !== benIdx) {
        if (benDetailAll) benDetailAll.classList.add('open');
        if (panel)        panel.classList.add('active');
        ben.classList.add('active');
        lastBenIdx = benIdx;
      } else {
        if (benDetailAll) benDetailAll.classList.remove('open');
        lastBenIdx = null;
      }
    });
  });

  /* ── Videos ── */
  document.querySelectorAll('.hf2-video-container').forEach(function (container) {
    container.addEventListener('click', function () {
      var url = container.dataset.videoUrl;
      if (!url) return;
      var ytMatch = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([A-Za-z0-9_-]{11})/);
      if (ytMatch) {
        var embedUrl = 'https://www.youtube.com/embed/' + ytMatch[1] + '?autoplay=1&rel=0';
        container.innerHTML = '<iframe src="' + embedUrl + '" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
      } else if (/\.(mp4|webm|ogg)$/i.test(url)) {
        container.innerHTML = '<video src="' + url + '" controls autoplay style="position:absolute;inset:0;width:100%;height:100%;background:#000"></video>';
      } else {
        container.innerHTML = '<iframe src="' + url + '?autoplay=1" allow="autoplay" allowfullscreen></iframe>';
      }
    });
  });

  /* ── Specs/Care accordion ── */
  document.querySelectorAll('.hf2-acc-trigger').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item   = btn.closest('.hf2-acc-item');
      var isOpen = item.classList.contains('open');
      document.querySelectorAll('.hf2-acc-item').forEach(function (i) {
        i.classList.remove('open');
        var b = i.querySelector('.hf2-acc-trigger');
        if (b) b.setAttribute('aria-expanded', 'false');
      });
      if (!isOpen) {
        item.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
      }
    });
  });

  /* ── Videos inside accordion toggles ── */
  document.querySelectorAll('.hf2-acc-video').forEach(function (container) {
    container.addEventListener('click', function () {
      var url = container.dataset.videoUrl;
      if (!url) return;
      var ytMatch = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([A-Za-z0-9_-]{11})/);
      if (ytMatch) {
        container.innerHTML = '<iframe src="https://www.youtube.com/embed/' + ytMatch[1] + '?autoplay=1&rel=0" allow="autoplay; encrypted-media" allowfullscreen style="position:absolute;inset:0;width:100%;height:100%;border:0;border-radius:10px"></iframe>';
      } else if (/\.(mp4|webm|ogg)$/i.test(url)) {
        container.innerHTML = '<video src="' + url + '" controls autoplay style="position:absolute;inset:0;width:100%;height:100%;background:#000;border-radius:10px"></video>';
      }
    });
  });

  /* ── FAQ accordion ── */
  document.querySelectorAll('.hf2-faq-trigger').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item   = btn.closest('.hf2-faq-item');
      var isOpen = item.classList.contains('open');
      document.querySelectorAll('.hf2-faq-item').forEach(function (i) {
        i.classList.remove('open');
        var b = i.querySelector('.hf2-faq-trigger');
        if (b) b.setAttribute('aria-expanded', 'false');
      });
      if (!isOpen) {
        item.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
      }
    });
  });

  /* ── Lightbox ── */
  var lightbox      = document.getElementById('hf2-lightbox');
  var lightboxInner = document.getElementById('hf2-lightbox-inner');
  var lightboxClose = document.getElementById('hf2-lightbox-close');

  function openLightbox(src, type) {
    if (!lightbox || !src) return;
    while (lightboxInner.children.length > 1) {
      lightboxInner.removeChild(lightboxInner.lastChild);
    }
    var media;
    if (type === 'video') {
      var ytMatch = src.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([A-Za-z0-9_-]{11})/);
      if (ytMatch) {
        media = document.createElement('iframe');
        media.src = 'https://www.youtube.com/embed/' + ytMatch[1] + '?autoplay=1&rel=0';
        media.allow = 'autoplay; encrypted-media';
        media.allowFullscreen = true;
      } else {
        media = document.createElement('video');
        media.src = src;
        media.controls = true;
        media.autoplay = true;
      }
    } else {
      media = document.createElement('img');
      media.src = src;
      media.alt = '';
    }
    lightboxInner.appendChild(media);
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    if (!lightbox) return;
    lightbox.classList.remove('open');
    document.body.style.overflow = '';
    while (lightboxInner.children.length > 1) {
      lightboxInner.removeChild(lightboxInner.lastChild);
    }
  }

  if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
  if (lightbox) {
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) closeLightbox();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeLightbox();
    });
  }

  /* ── Sticky buy bar — hidden by default, shows when cart scrolls out ── */
  var sticky    = document.getElementById('hf2-sticky');
  var cartSec   = document.getElementById('hf2-cart-section');
  var stickyBtn = document.getElementById('hf2-sticky-btn');

  if (sticky && cartSec) {
    function onScroll() {
      var rect = cartSec.getBoundingClientRect();
      if (rect.bottom < 0) {
        sticky.classList.remove('hidden');
      } else {
        sticky.classList.add('hidden');
      }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  if (stickyBtn && cartSec) {
    stickyBtn.addEventListener('click', function () {
      cartSec.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  }

})();
</script>

<?php
	endwhile;
?>

<?php
/* ── Footer: Elementor Theme Builder first, hello-elementor dynamic-footer as fallback ── */
if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'footer' ) ) {
	do_action( 'get_footer', null );
	if ( function_exists( 'hello_elementor_display_header_footer' ) && hello_elementor_display_header_footer() ) {
		if ( did_action( 'elementor/loaded' ) && function_exists( 'hello_header_footer_experiment_active' ) && hello_header_footer_experiment_active() ) {
			get_template_part( 'template-parts/dynamic-footer' );
		} else {
			get_template_part( 'template-parts/footer' );
		}
	}
}
wp_footer();
?>
</body>
</html>
