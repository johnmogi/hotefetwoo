<?php
/**
 * Plugin Name:  Haotfet Woo
 * Description:  All-in-one: custom fields + repeater admin UI, demo seed, bulk management, and a full product-page template that bypasses Elementor. Activate and go.
 * Version:      1.5.0
 * Author:       Dev
 * Text Domain:  haotfet-woo
 */
defined( 'ABSPATH' ) || exit;

define( 'HF_DIR', plugin_dir_path( __FILE__ ) );
define( 'HF_URL', plugin_dir_url( __FILE__ ) );
define( 'HF_VER', '1.7.0' );

/* ══════════════════════════════════════════════════════════
   0. ACTIVATION — disable old plugins, schedule first setup
   ══════════════════════════════════════════════════════════ */
register_activation_hook( __FILE__, function () {
	// Silence old competing plugins
	$old = [ 'haotfet-poc/haotfet-poc.php', 'haotfet-product-layout/haotfet-product-layout.php' ];
	foreach ( $old as $p ) {
		if ( is_plugin_active( $p ) ) deactivate_plugins( $p );
	}
	update_option( 'haotfet_activated_notice', 1 );
	flush_rewrite_rules();
} );

add_action( 'admin_notices', function () {
	if ( ! get_option( 'haotfet_activated_notice' ) ) return;
	if ( ! current_user_can( 'manage_options' ) ) return;
	delete_option( 'haotfet_activated_notice' );
	$url = admin_url( 'admin.php?page=haotfet-woo' );
	echo '<div class="notice notice-success is-dismissible" style="padding:12px 16px">'
		. '<strong style="font-size:14px">✅ Haotfet Woo פעיל!</strong> '
		. 'כדי להפעיל את התבנית על מוצר, לחצו <strong>Seed</strong> בדף הניהול. '
		. '<a href="' . esc_url( $url ) . '" class="button button-primary" style="margin-right:10px;vertical-align:middle">→ ניהול מוצרים</a>'
		. '</div>';
} );

/* ══════════════════════════════════════════════════════════
   1. REGISTER META FIELDS
   ══════════════════════════════════════════════════════════ */
add_action( 'init', function () {
	$simple = [
		'haot_subtitle'   => 'string',
		'haot_rating_val' => 'number',
		'haot_rating_cnt' => 'integer',
		'haot_desc'       => 'string',
		'haot_shipping'   => 'string',
	];
	foreach ( $simple as $k => $t ) {
		register_post_meta( 'product', $k, [ 'type' => $t, 'single' => true, 'show_in_rest' => true ] );
	}
	foreach ( [ 'haot_sizes', 'haot_benefits', 'haot_accordion', 'haot_faq', 'haot_how_to' ] as $k ) {
		register_post_meta( 'product', $k, [ 'type' => 'array', 'single' => true, 'show_in_rest' => false ] );
	}
} );

/* ══════════════════════════════════════════════════════════
   2. TEMPLATE OVERRIDE
   Bypass Elementor (priority 99 >> Elementor's 11),
   serve our template whenever a product has haotfet data
   OR has _haotfet_template = 'on'
   ══════════════════════════════════════════════════════════ */

/* ── Suppress conflicting output on our template pages ── */
add_action( 'wp', function () {
	if ( ! is_singular( 'product' ) || ! hf_template_active() ) return;
	// Kirki injects a custom-header image via wp_body_open — hide it on our pages
	if ( class_exists( 'Kirki\Frontend\TheFrontendHooks' ) ) {
		remove_action( 'wp_body_open', [ 'Kirki\Frontend\TheFrontendHooks', 'load_custom_header' ], 1 );
	}
	// Also remove core WP custom header in case theme outputs it separately
	remove_action( 'wp_head', 'wp_custom_header_markup', 10 );
	// Suppress WooCommerce default product image/flash (we render our own gallery)
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
} );

// Force our template at priority 99 (runs last)
add_filter( 'template_include', function ( $tpl ) {
	if ( is_singular( 'product' ) && hf_template_active() ) {
		$custom = HF_DIR . 'template-single-product.php';
		if ( file_exists( $custom ) ) return $custom;
	}
	return $tpl;
}, 99 );

function hf_template_active( $pid = 0 ) {
	if ( ! $pid ) $pid = get_queried_object_id();
	$override = get_post_meta( $pid, '_haotfet_template', true );
	if ( $override === 'off' )  return false;
	if ( $override === 'on'  )  return true;
	// Auto: active when any haotfet data exists
	return (bool) get_post_meta( $pid, 'haot_subtitle', true )
		|| (bool) get_post_meta( $pid, 'haot_benefits', true )
		|| (bool) get_post_meta( $pid, 'haot_accordion', true );
}

/* ══════════════════════════════════════════════════════════
   3. ENQUEUE — only on product pages using our template
   ══════════════════════════════════════════════════════════ */
add_action( 'wp_enqueue_scripts', function () {
	if ( is_singular( 'product' ) && hf_template_active() ) {
		// WooCommerce scripts still needed (cart, variations)
		wp_enqueue_script( 'wc-add-to-cart' );
		wp_enqueue_script( 'wc-add-to-cart-variation' );
	}
} );

/* ══════════════════════════════════════════════════════════
   4. ADMIN META BOX — all repeaters on product edit screen
   ══════════════════════════════════════════════════════════ */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'haotfet_fields', 'Haotfet — Product Content',
		'hf_metabox_render', 'product', 'normal', 'high' );
	add_meta_box( 'haotfet_template', 'Haotfet — Template',
		'hf_metabox_template_render', 'product', 'side', 'high' );
} );

function hf_metabox_render( $post ) {
	wp_nonce_field( 'hf_save', 'hf_nonce' );
	$pid = $post->ID;
	$sub   = get_post_meta( $pid, 'haot_subtitle',   true );
	$rv    = get_post_meta( $pid, 'haot_rating_val', true );
	$rc    = get_post_meta( $pid, 'haot_rating_cnt', true );
	$desc  = get_post_meta( $pid, 'haot_desc',       true );
	$ship  = get_post_meta( $pid, 'haot_shipping',   true );

	$sizes   = get_post_meta( $pid, 'haot_sizes',     true ) ?: [];
	$bens    = get_post_meta( $pid, 'haot_benefits',  true ) ?: [];
	$acc     = get_post_meta( $pid, 'haot_accordion', true ) ?: [];
	$faq     = get_post_meta( $pid, 'haot_faq',       true ) ?: [];
	$how     = get_post_meta( $pid, 'haot_how_to',    true ) ?: [];

	$seed_url  = wp_nonce_url( add_query_arg( [ 'page'=>'haotfet-woo','hf_seed'=>$pid  ], admin_url('admin.php') ), 'hf_seed_action' );
	$clear_url = wp_nonce_url( add_query_arg( [ 'page'=>'haotfet-woo','hf_clear'=>$pid ], admin_url('admin.php') ), 'hf_clear_action' );
	?>
<style>
.hf-mb{font-size:13px}
.hf-mb h4{margin:14px 0 5px;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#888;border-bottom:1px solid #eee;padding-bottom:3px}
.hf-row{display:grid;grid-template-columns:1fr 1fr;gap:10px 16px;margin-bottom:10px}
.hf-row.full{grid-template-columns:1fr}
.hf-mb label{display:block;font-weight:600;font-size:11px;color:#555;margin-bottom:2px}
.hf-mb input,.hf-mb textarea{width:100%;box-sizing:border-box;padding:5px 7px;border:1px solid #ddd;border-radius:3px;font-size:12px}
.hf-mb textarea{min-height:55px;resize:vertical}
.hf-rep{width:100%;border-collapse:collapse;margin-bottom:5px;font-size:12px}
.hf-rep th{background:#f5f5f5;padding:4px 6px;text-align:right;font-size:11px;font-weight:600;color:#555;border:1px solid #e0e0e0}
.hf-rep td{padding:3px 4px;border:1px solid #e8e8e8;vertical-align:top}
.hf-rep td input,.hf-rep td textarea,.hf-rep td select{width:100%;box-sizing:border-box;padding:3px 4px;border:1px solid #ddd;border-radius:2px;font-size:11px}
.hf-rep td textarea{min-height:44px;resize:vertical}
.hf-del{background:#c00;color:#fff;border:none;border-radius:2px;cursor:pointer;padding:3px 6px;font-size:11px;white-space:nowrap}
.hf-add{background:#2D6E3E;color:#fff;border:none;border-radius:3px;cursor:pointer;padding:5px 12px;font-size:12px;margin-top:3px}
.hf-actions{display:flex;gap:8px;margin-top:14px;padding-top:10px;border-top:1px solid #eee;flex-wrap:wrap}
.hf-btn-seed{background:#FFC929;color:#1c1c1c;border:none;border-radius:4px;cursor:pointer;padding:6px 14px;font-size:12px;font-weight:700;text-decoration:none}
.hf-btn-clear{background:#eee;color:#555;border:1px solid #ccc;border-radius:4px;padding:6px 14px;font-size:12px;text-decoration:none;cursor:pointer}
.hf-btn-clear:hover{background:#fdd;border-color:#c00;color:#c00}
</style>

<div class="hf-mb">

<h4>שדות בסיסיים</h4>
<div class="hf-row full">
  <div><label>Subtitle — שורת משנה</label>
  <input type="text" name="haot_subtitle" value="<?php echo esc_attr($sub);?>" placeholder="עוטפת בד שעווה מ-100% חומרים טבעיים"></div>
</div>
<div class="hf-row">
  <div><label>Rating value (0–5)</label>
  <input type="number" name="haot_rating_val" value="<?php echo esc_attr($rv);?>" min="0" max="5" step="0.1" placeholder="4.8"></div>
  <div><label>Rating count</label>
  <input type="number" name="haot_rating_cnt" value="<?php echo esc_attr($rc);?>" min="0" step="1" placeholder="142"></div>
</div>
<div class="hf-row full">
  <div><label>Description — תיאור (paragraph under rating)</label>
  <textarea name="haot_desc" placeholder="עוטפת בד שעווה מ-100% חומרים טבעיים…"><?php echo esc_textarea($desc);?></textarea></div>
</div>
<div class="hf-row full">
  <div><label>Shipping note — הערת משלוח (bottom of summary)</label>
  <input type="text" name="haot_shipping" value="<?php echo esc_attr($ship);?>" placeholder="✓ משלוח חינם מ-₪199 | ✓ איסוף עצמי"></div>
</div>

<!-- SIZES -->
<h4>Sizes — גדלים (<span id="hf-sizes-cnt"><?php echo count($sizes);?></span> rows)</h4>
<p style="font-size:11px;color:#888;margin:0 0 4px">שימושים: שורה לכל שימוש בפורמט <code>🍞 | כותרת | תיאור</code></p>
<table class="hf-rep"><thead><tr><th style="width:50px">label</th><th>dim</th><th>video URL</th><th>שימושים (icon | כותרת | תיאור)</th><th></th></tr></thead>
<tbody id="hf-sizes-body">
<?php foreach($sizes as $i=>$s):
  $uses_lines=[];
  foreach((array)($s['uses']??[]) as $uc){
    $uses_lines[]=($uc['icon']??'').' | '.($uc['title']??'').' | '.($uc['text']??'');
  }
  $uses_text=implode("\n",$uses_lines);
?>
<tr>
  <td style="width:50px"><input type="text" name="haot_sizes[<?=$i?>][label]" value="<?php echo esc_attr($s['label']??$s['size_label']??'');?>"></td>
  <td style="width:80px"><input type="text" name="haot_sizes[<?=$i?>][dim]"   value="<?php echo esc_attr($s['dim']??$s['size_dim']??'');?>"></td>
  <td style="width:160px"><input type="url" name="haot_sizes[<?=$i?>][video_url]" value="<?php echo esc_attr($s['video_url']??'');?>" placeholder="https://youtu.be/..."></td>
  <td><textarea name="haot_sizes[<?=$i?>][uses_text]" style="min-height:72px" placeholder="🥪 | כריך בית ספר | נשמר טרי עד הצהריים&#10;🧀 | גבינה | לא מתייבשת"><?php echo esc_textarea($uses_text);?></textarea></td>
  <td><button type="button" class="hf-del" onclick="hfDel(this,'hf-sizes-body','hf-sizes-cnt')">✕</button></td>
</tr>
<?php endforeach;?>
</tbody></table>
<button type="button" class="hf-add" onclick="hfAddSize()">+ גודל</button>

<!-- BENEFITS -->
<h4>Benefits — יתרונות (<span id="hf-bens-cnt"><?php echo count($bens);?></span> rows)</h4>
<table class="hf-rep"><thead><tr><th>icon</th><th>title</th><th>sub</th><th></th></tr></thead>
<tbody id="hf-bens-body">
<?php foreach($bens as $i=>$b):?>
<tr>
  <td style="width:60px"><input type="text" name="haot_benefits[<?=$i?>][icon]"  value="<?php echo esc_attr($b['icon']??'');?>"></td>
  <td><input type="text" name="haot_benefits[<?=$i?>][title]"                    value="<?php echo esc_attr($b['title']??'');?>"></td>
  <td><input type="text" name="haot_benefits[<?=$i?>][sub]"                      value="<?php echo esc_attr($b['sub']??$b['subtitle']??'');?>"></td>
  <td><button type="button" class="hf-del" onclick="hfDel(this,'hf-bens-body','hf-bens-cnt')">✕</button></td>
</tr>
<?php endforeach;?>
</tbody></table>
<button type="button" class="hf-add" onclick="hfAdd('hf-bens-body','hf-bens-cnt','haot_benefits',['icon','title','sub'],['emoji','title','subtitle'])">+ יתרון</button>

<!-- ACCORDION -->
<h4>Accordion — פרטי מוצר (<span id="hf-acc-cnt"><?php echo count($acc);?></span> rows)</h4>
<table class="hf-rep"><thead><tr><th>icon</th><th>title</th><th>body</th><th>video URL (optional)</th><th>open?</th><th></th></tr></thead>
<tbody id="hf-acc-body">
<?php foreach($acc as $i=>$a):?>
<tr>
  <td style="width:50px"><input type="text" name="haot_accordion[<?=$i?>][icon]"      value="<?php echo esc_attr($a['icon']??'');?>"></td>
  <td><input type="text" name="haot_accordion[<?=$i?>][title]"                        value="<?php echo esc_attr($a['title']??'');?>"></td>
  <td><textarea name="haot_accordion[<?=$i?>][body]"><?php echo esc_textarea($a['body']??'');?></textarea></td>
  <td><input type="url" name="haot_accordion[<?=$i?>][video_url]"                     value="<?php echo esc_attr($a['video_url']??'');?>" placeholder="https://youtu.be/..."></td>
  <td style="width:50px;text-align:center"><input type="checkbox" name="haot_accordion[<?=$i?>][open]" value="1" <?php checked(!empty($a['open']));?>></td>
  <td><button type="button" class="hf-del" onclick="hfDel(this,'hf-acc-body','hf-acc-cnt')">✕</button></td>
</tr>
<?php endforeach;?>
</tbody></table>
<button type="button" class="hf-add" onclick="hfAddAcc()">+ פריט</button>

<!-- FAQ -->
<h4>FAQ — שאלות נפוצות (<span id="hf-faq-cnt"><?php echo count($faq);?></span> rows)</h4>
<table class="hf-rep"><thead><tr><th>question</th><th>answer</th><th></th></tr></thead>
<tbody id="hf-faq-body">
<?php foreach($faq as $i=>$f):?>
<tr>
  <td><input type="text" name="haot_faq[<?=$i?>][q]" value="<?php echo esc_attr($f['q']??$f['question']??'');?>"></td>
  <td><textarea name="haot_faq[<?=$i?>][a]"><?php echo esc_textarea($f['a']??$f['answer']??'');?></textarea></td>
  <td><button type="button" class="hf-del" onclick="hfDel(this,'hf-faq-body','hf-faq-cnt')">✕</button></td>
</tr>
<?php endforeach;?>
</tbody></table>
<button type="button" class="hf-add" onclick="hfAdd('hf-faq-body','hf-faq-cnt','haot_faq',['q','a'],['שאלה','תשובה'])">+ שאלה</button>

<!-- HOW TO -->
<h4>How to use — איך משתמשים (<span id="hf-how-cnt"><?php echo count($how);?></span> rows)</h4>
<table class="hf-rep"><thead><tr><th>num</th><th>label</th><th>sub</th><th></th></tr></thead>
<tbody id="hf-how-body">
<?php foreach($how as $i=>$h):?>
<tr>
  <td style="width:50px"><input type="text" name="haot_how_to[<?=$i?>][num]"   value="<?php echo esc_attr($h['num']??$h['step_num']??'');?>"></td>
  <td><input type="text" name="haot_how_to[<?=$i?>][label]"                    value="<?php echo esc_attr($h['label']??'');?>"></td>
  <td><input type="text" name="haot_how_to[<?=$i?>][sub]"                      value="<?php echo esc_attr($h['sub']??'');?>"></td>
  <td><button type="button" class="hf-del" onclick="hfDel(this,'hf-how-body','hf-how-cnt')">✕</button></td>
</tr>
<?php endforeach;?>
</tbody></table>
<button type="button" class="hf-add" onclick="hfAdd('hf-how-body','hf-how-cnt','haot_how_to',['num','label','sub'],['01','צעד','תיאור'])">+ שלב</button>

<!-- ACTIONS -->
<div class="hf-actions">
  <a href="<?php echo esc_url($seed_url);?>" class="hf-btn-seed">⟳ Seed demo data</a>
  <a href="<?php echo esc_url($clear_url);?>" class="hf-btn-clear"
     onclick="return confirm('Clear all haotfet fields?')">✕ Clear</a>
</div>

</div><!-- .hf-mb -->

<script>
var hfIdx={};
function hfDel(btn,bodyId,cntId){
  btn.closest('tr').remove();
  hfReindex(bodyId);
  document.getElementById(cntId).textContent=document.getElementById(bodyId).querySelectorAll('tr').length;
}
function hfReindex(bodyId){
  document.getElementById(bodyId).querySelectorAll('tr').forEach(function(tr,i){
    tr.querySelectorAll('input,textarea').forEach(function(el){
      el.name=el.name.replace(/\[\d+\]/,'['+i+']');
    });
  });
}
function hfAdd(bodyId,cntId,prefix,fields,placeholders){
  if(hfIdx[bodyId]===undefined) hfIdx[bodyId]=document.getElementById(bodyId).querySelectorAll('tr').length;
  var i=hfIdx[bodyId]++;
  var tr=document.createElement('tr');
  fields.forEach(function(f,fi){
    var td=document.createElement('td');
    var isTA=(f==='a'||f==='body'||f==='answer');
    var el=document.createElement(isTA?'textarea':'input');
    if(!isTA){el.type='text';if(f==='num')el.style.width='50px';if(f==='icon')el.style.width='60px';}
    el.name=prefix+'['+i+']['+f+']';
    el.placeholder=placeholders[fi]||f;
    if(isTA){el.style.minHeight='44px';el.style.resize='vertical';}
    td.appendChild(el);
    tr.appendChild(td);
  });
  var tdDel=document.createElement('td');
  tdDel.innerHTML='<button type="button" class="hf-del" onclick="hfDel(this,\''+bodyId+'\',\''+cntId+'\')">✕</button>';
  tr.appendChild(tdDel);
  document.getElementById(bodyId).appendChild(tr);
  document.getElementById(cntId).textContent=document.getElementById(bodyId).querySelectorAll('tr').length;
}
function hfAddSize(){
  if(hfIdx['hf-sizes-body']===undefined) hfIdx['hf-sizes-body']=document.getElementById('hf-sizes-body').querySelectorAll('tr').length;
  var i=hfIdx['hf-sizes-body']++;
  var tr=document.createElement('tr');
  tr.innerHTML='<td style="width:50px"><input type="text" name="haot_sizes['+i+'][label]" placeholder="S"></td>'
    +'<td style="width:80px"><input type="text" name="haot_sizes['+i+'][dim]" placeholder="15×15 ס&quot;מ"></td>'
    +'<td style="width:160px"><input type="url" name="haot_sizes['+i+'][video_url]" placeholder="https://youtu.be/..."></td>'
    +'<td><textarea name="haot_sizes['+i+'][uses_text]" style="min-height:72px;resize:vertical;width:100%" placeholder="🥪 | כריך בית ספר | נשמר טרי עד הצהריים&#10;🧀 | גבינה | לא מתייבשת"></textarea></td>'
    +'<td><button type="button" class="hf-del" onclick="hfDel(this,\'hf-sizes-body\',\'hf-sizes-cnt\')">✕</button></td>';
  document.getElementById('hf-sizes-body').appendChild(tr);
  document.getElementById('hf-sizes-cnt').textContent=document.getElementById('hf-sizes-body').querySelectorAll('tr').length;
}
function hfAddAcc(){
  var i=hfIdx['hf-acc-body']===undefined?(hfIdx['hf-acc-body']=document.getElementById('hf-acc-body').querySelectorAll('tr').length):hfIdx['hf-acc-body']++;
  var tr=document.createElement('tr');
  tr.innerHTML='<td><input type="text" name="haot_accordion['+i+'][icon]" placeholder="📋"></td>'
    +'<td><input type="text" name="haot_accordion['+i+'][title]" placeholder="כותרת"></td>'
    +'<td><textarea name="haot_accordion['+i+'][body]" style="min-height:44px;resize:vertical;width:100%"></textarea></td>'
    +'<td><input type="url" name="haot_accordion['+i+'][video_url]" placeholder="https://youtu.be/..." style="width:100%"></td>'
    +'<td style="text-align:center"><input type="checkbox" name="haot_accordion['+i+'][open]" value="1"></td>'
    +'<td><button type="button" class="hf-del" onclick="hfDel(this,\'hf-acc-body\',\'hf-acc-cnt\')">✕</button></td>';
  document.getElementById('hf-acc-body').appendChild(tr);
  document.getElementById('hf-acc-cnt').textContent=document.getElementById('hf-acc-body').querySelectorAll('tr').length;
}
</script>
	<?php
}

function hf_metabox_template_render( $post ) {
	wp_nonce_field( 'hf_template_save', 'hf_template_nonce' );
	$val = get_post_meta( $post->ID, '_haotfet_template', true ) ?: 'auto';
	?>
	<select name="_haotfet_template" style="width:100%;margin-bottom:8px">
		<option value="auto"  <?php selected($val,'auto'); ?>>Auto (on if data exists)</option>
		<option value="on"    <?php selected($val,'on');   ?>>Always ON</option>
		<option value="off"   <?php selected($val,'off');  ?>>OFF (theme default)</option>
	</select>
	<p style="font-size:11px;color:#888;margin:0">
		When ON/Auto: Haotfet PHP template replaces Elementor.<br>
		<a href="<?php echo esc_url(admin_url('admin.php?page=haotfet-woo')); ?>">→ Overview</a>
	</p>
	<?php
}

/* ══════════════════════════════════════════════════════════
   5. SAVE META BOX
   ══════════════════════════════════════════════════════════ */
add_action( 'save_post_product', function ( $pid ) {
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
	if ( wp_is_post_revision($pid) ) return;

	// Template selector
	if ( isset($_POST['hf_template_nonce']) && wp_verify_nonce($_POST['hf_template_nonce'],'hf_template_save') ) {
		update_post_meta( $pid, '_haotfet_template', sanitize_text_field($_POST['_haotfet_template'] ?? 'auto') );
	}

	// Fields
	if ( ! isset($_POST['hf_nonce']) || ! wp_verify_nonce($_POST['hf_nonce'],'hf_save') ) return;
	if ( ! current_user_can('edit_post',$pid) ) return;

	$simple = [
		'haot_subtitle'   => 'sanitize_text_field',
		'haot_rating_val' => 'floatval',
		'haot_rating_cnt' => 'intval',
		'haot_desc'       => 'sanitize_textarea_field',
		'haot_shipping'   => 'sanitize_text_field',
	];
	foreach ( $simple as $k => $fn ) {
		if ( isset($_POST[$k]) ) update_post_meta($pid,$k,call_user_func($fn,wp_unslash($_POST[$k])));
	}

	// Sizes — preserve all seeded fields, only overwrite what admin shows
	if ( isset($_POST['haot_sizes']) ) {
		$existing = get_post_meta($pid,'haot_sizes',true) ?: [];
		$rows=[];
		foreach((array)$_POST['haot_sizes'] as $idx=>$r){
			$prev = isset($existing[$idx]) ? (array)$existing[$idx] : [];
			// Parse uses_text into structured array
			$uses=[];
			foreach(explode("\n",trim(wp_unslash($r['uses_text']??''))) as $line){
				$line=trim($line); if(!$line) continue;
				$parts=array_map('trim',explode('|',$line,3));
				if(count($parts)>=2){
					$uses[]=['icon'=>sanitize_text_field($parts[0]??''),'title'=>sanitize_text_field($parts[1]??''),'text'=>sanitize_text_field($parts[2]??'')];
				}
			}
			$rows[]=array_merge($prev,[
				'label'     => sanitize_text_field(wp_unslash($r['label']??'')),
				'dim'       => sanitize_text_field(wp_unslash($r['dim']??'')),
				'video_url' => esc_url_raw(wp_unslash($r['video_url']??'')),
				'uses'      => $uses,
			]);
		}
		update_post_meta($pid,'haot_sizes',$rows);
	}

	// Benefits
	if ( isset($_POST['haot_benefits']) ) {
		$rows=[];
		foreach((array)$_POST['haot_benefits'] as $r){
			$rows[]=['icon'=>sanitize_text_field(wp_unslash($r['icon']??'')),'title'=>sanitize_text_field(wp_unslash($r['title']??'')),'sub'=>sanitize_text_field(wp_unslash($r['sub']??''))];
		}
		update_post_meta($pid,'haot_benefits',$rows);
	}

	// Accordion
	if ( isset($_POST['haot_accordion']) ) {
		$rows=[];
		foreach((array)$_POST['haot_accordion'] as $r){
			$rows[]=[
				'icon'      => sanitize_text_field(wp_unslash($r['icon']??'')),
				'title'     => sanitize_text_field(wp_unslash($r['title']??'')),
				'body'      => sanitize_textarea_field(wp_unslash($r['body']??'')),
				'video_url' => esc_url_raw(wp_unslash($r['video_url']??'')),
				'open'      => !empty($r['open'])?1:0,
			];
		}
		update_post_meta($pid,'haot_accordion',$rows);
	}

	// FAQ
	if ( isset($_POST['haot_faq']) ) {
		$rows=[];
		foreach((array)$_POST['haot_faq'] as $r){
			$rows[]=['q'=>sanitize_text_field(wp_unslash($r['q']??'')),'a'=>sanitize_textarea_field(wp_unslash($r['a']??''))];
		}
		update_post_meta($pid,'haot_faq',$rows);
	}

	// How to
	if ( isset($_POST['haot_how_to']) ) {
		$rows=[];
		foreach((array)$_POST['haot_how_to'] as $r){
			$rows[]=['num'=>sanitize_text_field(wp_unslash($r['num']??'')),'label'=>sanitize_text_field(wp_unslash($r['label']??'')),'sub'=>sanitize_text_field(wp_unslash($r['sub']??''))];
		}
		update_post_meta($pid,'haot_how_to',$rows);
	}
} );

/* ══════════════════════════════════════════════════════════
   6. SEED DATA
   ══════════════════════════════════════════════════════════ */
function hf_seed_data() {
	return [
		/* ── Announce bar ── */
		'haot_announce'   => [
			'🏠 משלוח חינם עד הבית בקנייה מעל ₪299',
			'📦 איסוף חינם מנקודת איסוף מעל ₪169',
			'🌿 100% חומרים טבעיים וישראלי',
		],

		/* ── Basic fields ── */
		'haot_subtitle'   => 'עוטפת בד שעווה ציפורים בשדה — מ-100% חומרים טבעיים, כותנה, שעווה נקיה, שמן חוחובה ושרף אורן',
		'haot_rating_val' => 4.8,
		'haot_rating_cnt' => 142,
		'haot_desc'       => 'מחליפה ניילון נצמד ונמשכת עד 100 שימושים. עוטפת ציפורים בשדה — עיצוב ישראלי מיוחד שמשלב יופי ותועלת.',
		'haot_desc_full'  => "מה בדיוק בחבילה?\n3 שקיות הדרך + 1 XL + 3 L + 3 M + 2 S — סה\"כ 12 פריטים שמכסים כל צורך במטבח.\n\nלמה זה עובד?\nהשעווה הנקיה נאטמת עם חום כפות הידיים ומחזיקה את המזון טרי יותר. הציפוי אנטיבקטריאלי מטבעו — מונע ריחות, עובש, והתייבשות. בד כותנה OEKO-TEX 100, ללא BPA, ללא כימיקלים.\n\nמה אפשר לעטוף?\nפירות, ירקות, גבינה, לחם, כריכים, קערות פתוחות, שאריות ארוחה, חצאי פירות — כמעט הכל. לא מתאים: בשר ודגים גולמיים, מיקרוגל, תנור, מים חמים מעל 40°C.\n\nכמה זמן מחזיקה?\nעד 100 שימושים עם שמירה נכונה. ככל שמשתמשים יותר, כך הריח הטבעי של השעווה מתמתן ומשתפר.\n\nניקוי ותחזוקה:\nמים קרים + סבון כלים ביד — מתייבשת תוך דקות. אחסנו מקופלת במגירה. כשהציפוי מתבלה, ניתן לרכוש ערכת התחדשות בנפרד.\n\nתוצרת ישראל — עוצב ויוצר מחומרים ישראליים.",
		'haot_shipping'   => '✓ משלוח חינם מ-₪299  |  ✓ 100% טבעי  |  ✓ תוצרת ישראל',

		/* ── Trust badges ── */
		'haot_trust'      => [
			[ 'icon_url' => HF_URL . 'assets/brand/redesign/img_41.png',  'label' => 'ישראלי 100%' ],
			[ 'icon_url' => HF_URL . 'assets/brand/redesign/img_42.png',  'label' => 'עד 100 שימושים' ],
			[ 'icon_url' => HF_URL . 'assets/brand/redesign/img_43.webp', 'label' => '100% טבעי' ],
		],


		/* ── Sizes ── */
		'haot_sizes'      => [
			[
				'label'      => 'S',
				'dim'        => '15×15 ס"מ',
				'count'      => '×2',
				'qty'        => 'קטן',
				'use_title'  => 'לכריכים וגבינות',
				'use_text'   => "מושלמת לעטיפת כריך, פרוסת גבינה אחת, חצי ירק חתוך, או קטע תפוח. \n• שומרת על הגבינה רכה ולא מתייבשת\n• כריך לבית הספר נשמר טרי עד הצהריים\n• חצי לימון, קיווי או אבוקדו — רעננים ליום שאחרי",
				'solves'     => 'ניילון נצמד, שקית ניילון קטנה, נייר כסף',
				'thumb_url'  => HF_URL . 'assets/brand/redesign/img_11.webp',
				'panel_img'  => HF_URL . 'assets/brand/redesign/img_20.webp',
				'uses'       => [
					[ 'icon' => '🥑', 'title' => 'אבוקדו פתוח',       'img' => HF_URL . 'assets/brand/redesign/img_16.webp',  'text' => "מכסה את הצד החתוך בדיוק — ממש כמו עור.\nהשעווה יוצרת אטימה שמונעת חמצון.\nהאבוקדו נשאר ירוק 8-12 שעות בלי לימון.", 'saves' => 'ניילון נצמד + גומייה' ],
					[ 'icon' => '🧀', 'title' => 'פרוסות גבינה',       'img' => HF_URL . 'assets/brand/redesign/img_17.webp',  'text' => "עוטפת 2-3 פרוסות בלי שיידבקו זו לזו.\nהגבינה נושמת — לא מזיעה, לא מסריחה.\nפותחים וסוגרים כמה פעמים בלי לאבד את האטימה.", 'saves' => 'ניילון נצמד, נייר כסף' ],
					[ 'icon' => '🍋', 'title' => 'חצי הדר',            'img' => HF_URL . 'assets/brand/redesign/img_18.webp',  'text' => "מושלמת לחצי לימון, תפוז, קלמנטינה.\nהריח עוצר לגמרי — שאר הירקות במקרר שמחים.\nמחזיקה כשבוע בקלות.", 'saves' => 'שקית ניילון, כיסוי פלסטיק' ],
					[ 'icon' => '🍱', 'title' => 'ירקות לקופסת בנטו', 'img' => HF_URL . 'assets/brand/redesign/img_19.webp',  'text' => "עוטפת חצאי מלפפון, גזר קטוע, פרוסת גבינה — כל אחד בנפרד.\nאין תערובת ריחות, אין לחות מיותרת.\nהילד פותח, אוכל, ומחזיר לתיק — אין פסולת.", 'saves' => 'שקיות ניילון נפרדות לכל ירק' ],
				],
			],
			[
				'label'      => 'M',
				'dim'        => '25×25 ס"מ',
				'count'      => '×3',
				'qty'        => 'פופולרי',
				'use_title'  => 'לפירות וירקות',
				'use_text'   => "עוטפת תפוח, מלפפון, פלפל, עגבניה — שומרת על טריות ומונעת ריחות. \n• מכסה קערה קטנה של סלט חתוך\n• עוטפת גבינה קשה שלמה\n• מחזיקה כריך בינוני לפיקניק\n• מגינה על יוגורט פתוח במקרר",
				'solves'     => 'ניילון נצמד, שקית ניילון רגילה, כיסוי פלסטיק לקערה',
				'thumb_url'  => HF_URL . 'assets/brand/redesign/img_12.webp',
				'panel_img'  => HF_URL . 'assets/brand/redesign/img_25.webp',
				'uses'       => [
					[ 'icon' => '🧅', 'title' => 'בצל חתוך במקרר',      'img' => HF_URL . 'assets/brand/redesign/img_21.webp', 'text' => "עוטפת חצי בצל ועוצרת את הריח לגמרי.\nהמקרר מרוצה — שאר האוכל לא קולט ריח.\nהבצל נשמר חתוך ורענן 3-4 ימים.", 'saves' => 'שקית ניילון + ריח בכל המקרר' ],
					[ 'icon' => '🧀', 'title' => 'גבינה קשה שלמה',      'img' => HF_URL . 'assets/brand/redesign/img_22.webp',  'text' => "מאפשרת לגבינה לנשום ולא להזיע.\nמונעת קשיחות בקצוות ועובש מהיר.\nמחליפה ניילון שמחניק ומוסיף ריח פלסטיק.", 'saves' => 'ניילון נצמד, שקית מזיפ-לוק' ],
					[ 'icon' => '🥦', 'title' => 'ירקות חתוכים לעבודה', 'img' => HF_URL . 'assets/brand/redesign/img_23.webp', 'text' => "פלפל חצוי, מלפפון, ברוקולי — עוטפת בדיוק.\nלא מרטיבה את שאר התיק, לא מגמישה בחום.\nמגיעה לעבודה כמו שיצאה מהמטבח.", 'saves' => 'שקית ניילון, מיכל פלסטיק' ],
					[ 'icon' => '🍚', 'title' => 'שאריות אורז/פסטה',    'img' => HF_URL . 'assets/brand/redesign/img_24.webp',  'text' => "מכסה קערה עם שאריות ישר להכנסה למקרר.\nאין צורך לחפש מכסה — מתאימה לכל גודל.\nמגינה 2-3 ימים בלי ייבוש וריחות.", 'saves' => 'פויל אלומיניום, ניילון נצמד' ],
				],
			],
			[
				'label'      => 'L',
				'dim'        => '35×35 ס"מ',
				'count'      => '×3',
				'qty'        => 'נפוץ',
				'use_title'  => 'לקערות ולחם',
				'use_text'   => "מכסה קערה בינונית, עוטפת כיכר לחם שלמה, או מגינה על שאריות ארוחה. \n• אוטמת קערת סלט לאחסון במקרר\n• שומרת על לחם טרי עד 3 ימים\n• מכסה בצק מחמצת בזמן התפחה\n• מחליפה את הפויל על תבנית בתנור (עד 40°C)",
				'solves'     => 'פויל אלומיניום, כיסוי ניילון לקערה, שקית לחם חד-פעמית',
				'thumb_url'  => HF_URL . 'assets/brand/redesign/img_13.webp',
				'panel_img'  => HF_URL . 'assets/brand/redesign/img_30.webp',
				'uses'       => [
					[ 'icon' => '🍞', 'title' => 'לחם מחמצת / כיכר', 'img' => HF_URL . 'assets/brand/redesign/img_26.webp',  'text' => "הלחם נושם — הקראסט נשמר פריך 2-3 ימים.\nלא מתייבש, לא מתעפש, לא מאבד צורה.\nפשוט לקפל מחדש ולשמור בארון בלי מכסה.", 'saves' => 'שקית ניילון, קופסת פח' ],
					[ 'icon' => '🥖', 'title' => 'בצק מחמצת בהתפחה', 'img' => HF_URL . 'assets/brand/redesign/img_27.webp',  'text' => "מאפשרת אוויר מבוקר — הבצק נושם ותופח נכון.\nלא מתייבשת מבחוץ, לא נדבקת לבצק.\nמחליפה מגבת לחה שצריך לשטוף אחרי.", 'saves' => 'מגבת לחה, ניילון נצמד' ],
					[ 'icon' => '🥗', 'title' => 'סלט מוכן מראש',    'img' => HF_URL . 'assets/brand/redesign/img_28.webp',  'text' => "מכינים סלט גדול ביום א', מכסים ושמים במקרר.\nהירקות לא מאבדים מיץ ונשארים פריכים יומיים.\nמוציאים, מגישים, מכסים — לא צריך כלי אחר.", 'saves' => 'ניילון נצמד, מכסה פלסטיק' ],
					[ 'icon' => '🥧', 'title' => 'תבנית פאי / קיש',  'img' => HF_URL . 'assets/brand/redesign/img_29.webp',  'text' => "מגינה על תבנית 28 ס\"מ בין אפייה להגשה.\nאין צורך בפויל — מגן בלי מגע עם האוכל.\nניתן לאחסן שאריות ישר בתבנית.", 'saves' => 'פויל אלומיניום, ניילון נצמד' ],
				],
			],
			[
				'label'      => 'XL',
				'dim'        => '45×60 ס"מ',
				'count'      => '×1',
				'qty'        => 'גדול',
				'use_title'  => 'לקערות גדולות ופיקניק',
				'use_text'   => "המידה הגדולה ביותר — אין כמעט מה שלא תכסה. \n• מגש פיקניק שלם עם מאכלים\n• קערת מיקסר גדולה בזמן התפחת בצק\n• כיכר לחם גדולה או חלות\n• מכסה סלטון משפחתי לאחסון",
				'solves'     => 'פויל אלומיניום גדול, ניילון נצמד רחב, מכסה פלסטיק',
				'thumb_url'  => HF_URL . 'assets/brand/redesign/img_14.webp',
				'panel_img'  => HF_URL . 'assets/brand/redesign/img_35.webp',
				'uses'       => [
					[ 'icon' => '🍉', 'title' => 'חצי אבטיח',          'img' => HF_URL . 'assets/brand/redesign/img_31.webp',  'text' => "מכסה חצי אבטיח 30-35 ס\"מ — ממש בדיוק.\nמגינה מחרקים ואבק כשהוא עומד על השולחן.\nלא מחליקה, לא נקרעת — מחזיקה חזק.", 'saves' => 'ניילון נצמד רחב, פויל כפול' ],
					[ 'icon' => '🫕', 'title' => 'קדירה / סיר גדול',   'img' => HF_URL . 'assets/brand/redesign/img_32.webp',  'text' => "מכסה סיר גדול 30-40 ס\"מ ישר להכנסה למקרר.\nהמכסה אבד? — הפתרון הנכון.\nמגינה מריחות, שומרת לחות 2-3 ימים.", 'saves' => 'ניילון נצמד, פויל, מכסה פלסטיק' ],
					[ 'icon' => '🫓', 'title' => 'חלות ולחמים גדולים', 'img' => HF_URL . 'assets/brand/redesign/img_33.webp',  'text' => "עוטפת שתי חלות יחד בכבוד — ייצוגית ויפה.\nהרכות נשמרת לכבוד השולחן, הקראסט לא מתייבש.\nניתן להשאיר על השולחן — נראית מיוחדת.", 'saves' => 'שקית ניילון, מגבת' ],
					[ 'icon' => '🎂', 'title' => 'עוגת יום הולדת',      'img' => HF_URL . 'assets/brand/redesign/img_34.webp',  'text' => "עוטפת עוגה שלמה 28-32 ס\"מ אחרי החיתוך.\nמגינה על הקישוטים והציפוי בלי מגע.\nשומרת לחות ורעננות ב-24 שעות הראשונות.", 'saves' => 'ניילון נצמד, כיפת פלסטיק' ],
				],
			],
			[
				'label'      => 'שקיות הדרך',
				'dim'        => '18×22 ס"מ',
				'count'      => '×3',
				'note'       => 'ללא שעווה — בד כותנה טבעי',
				'qty'        => 'חדש',
				'use_title'  => 'לכריכים בדרכים',
				'use_text'   => "שקיות בד שעווה לשאיפה — נכנסים בתוכן, מקפלים את הפה, ויוצאים לדרך. \n• כריך שלם לצהריים בלי ניילון\n• חטיפים טבעיים בתיק\n• לחמניות, פיתות ולחמים מיוחדים\n• אידיאליות לטיולים, גינה, ים וטבע",
				'solves'     => 'שקית ניילון חד-פעמית, נייר כסף, ניילון גלופה',
				'thumb_url'  => HF_URL . 'assets/brand/redesign/img_15.webp',
				'panel_img'  => HF_URL . 'assets/brand/redesign/img_40.webp',
				'uses'       => [
					[ 'icon' => '🥙', 'title' => 'פיתה / שווארמה',     'img' => HF_URL . 'assets/brand/redesign/img_36.webp',  'text' => "נסגרת הרמטית — ללא נזילות בתיק.\nהפיתה לא נהיית סמרטוטית, המלית שומרת חום.\nפותחים בדרך, אוכלים, מקפלים — זהו.", 'saves' => 'נייר כסף, ניילון גלופה' ],
					[ 'icon' => '🥐', 'title' => 'קרואסון / מאפה בוקר', 'img' => HF_URL . 'assets/brand/redesign/img_37.webp',  'text' => "מגינה על המאפה ושומרת על הצורה בתיק.\nהחמאה לא בורחת — לא מרטיבה את הציוד.\nניתן לחמם קלות בתנור עד 40°C כשהיא סגורה.", 'saves' => 'שקית ניילון, נייר כסף' ],
					[ 'icon' => '🫐', 'title' => 'פירות יער לטיול',    'img' => HF_URL . 'assets/brand/redesign/img_38.webp',  'text' => "מחזיקה 200-300 גרם בדיוק — לא נוזל, לא כתמים.\nנסגרת חזק גם כשמלאה עד הסוף.\nניקוי מהיר — הנהרה קצרה ומייבשת על אוויר.", 'saves' => 'קופסת פלסטיק, שקית ניילון' ],
					[ 'icon' => '🧁', 'title' => 'עוגיות / מאפה לילד',  'img' => HF_URL . 'assets/brand/redesign/img_39.webp',  'text' => "עטופות יפה, לא נמעכות בתיק הילד.\nמגינה על הצורה — מגיעה שלמה ליעד.\nנראית כמו מתנה קטנה — לא עוד שקית ניילון.", 'saves' => 'קופסה, ניילון, נייר אפייה' ],
				],
			],
		],

		/* ── Benefits (real brand icons) ── */
		'haot_benefits'   => [
			[
				'icon'     => '',
				'icon_url' => HF_URL . 'assets/brand/imgi_64_width_494.png',
				'title'    => 'אנטיבקטריאלי',
				'sub'      => 'השעווה הנקיה מונעת התפתחות חיידקים',
			],
			[
				'icon'     => '',
				'icon_url' => HF_URL . 'assets/brand/imgi_66_width_550.png',
				'title'    => 'שימוש חוזר',
				'sub'      => 'עד 100 שימושים — לשטוף במים קרים',
			],
			[
				'icon'     => '',
				'icon_url' => HF_URL . 'assets/brand/imgi_68_width_525.webp',
				'title'    => 'מיוצר בישראל',
				'sub'      => 'עיצוב וייצור מקומי 100%',
			],
			[
				'icon'     => '',
				'icon_url' => HF_URL . 'assets/brand/imgi_70_width_394.png',
				'title'    => '100% טבעי',
				'sub'      => 'OEKO-TEX 100, ללא BPA, ללא כימיקלים',
			],
		],

		/* ── Gallery main video ── */
		'haot_main_video' => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',

		/* ── Videos (5 categories — placeholders until real footage) ── */
		'haot_videos'     => [
			[
				'title'     => 'הכירי את ציפורים בשדה',
				'url'       => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
				'thumb_url' => HF_URL . 'assets/brand/imgi_5_width_550.webp',
				'desc'      => 'מה זה עוטפת בד שעווה ולמה היא תחליף מושלם לניילון נצמד.',
			],
			[
				'title'     => 'איך עוטפים? שימוש יומיומי',
				'url'       => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
				'thumb_url' => HF_URL . 'assets/brand/imgi_8_width_550.webp',
				'desc'      => 'הדגמה מלאה — חממי, עטפי, אחסני.',
			],
			[
				'title'     => 'איך מנקים? שטיפה ותחזוקה',
				'url'       => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
				'thumb_url' => HF_URL . 'assets/brand/imgi_9_width_550.png',
				'desc'      => 'מים קרים + סבון כלים ביד — כך שומרים על העוטפת לאורך זמן.',
			],
			[
				'title'     => 'כמה שנים מחזיקה? שימוש חוזר',
				'url'       => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
				'thumb_url' => HF_URL . 'assets/brand/imgi_11_width_550.webp',
				'desc'      => 'עד 100 שימושים — ומה עושים כשהציפוי מתבלה.',
			],
			[
				'title'     => 'לקוחות ממליצות',
				'url'       => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
				'thumb_url' => HF_URL . 'assets/brand/imgi_13_width_550.webp',
				'desc'      => 'מה אומרות לקוחות שמשתמשות בציפורים בשדה כבר חצי שנה.',
			],
		],

		/* ── Accordion ── */
		'haot_accordion'  => [
			[
				'icon'      => '📋',
				'title'     => 'מפרט וחומרים',
				'body'      => 'בד כותנה OEKO-TEX 100 · שעווה נקיה (ממגדל איציק) · שמן חוחובה (קיבוץ חצרים) · שרף אורן. לא מתאים: בשר/דגים גולמיים, מיקרוגל, מים חמים.',
				'video_url' => '',
				'open'      => 1,
			],
			[
				'icon'      => '🧼',
				'title'     => 'שטיפה ותחזוקה',
				'body'      => 'מים קרים + סבון כלים ביד · מתייבשת מהר · אין מדיח כלים.',
				'video_url' => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
				'open'      => 0,
			],
			[
				'icon'      => '♻️',
				'title'     => 'שימוש חוזר ואורך חיים',
				'body'      => 'עד 100 שימושים · ככל שמשתמשים יותר, כך הריח פוחת ומשתפר. ניתן לרכוש ציפוי התחדשות.',
				'video_url' => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
				'open'      => 0,
			],
			[
				'icon'      => '📦',
				'title'     => 'משלוח ואריזה',
				'body'      => 'מגיעה בקופסת מתנה ממוחזרת · משלוח חינם בקנייה מעל ₪299 · איסוף עצמי בחינם.',
				'video_url' => '',
				'open'      => 0,
			],
		],

		/* ── FAQ ── */
		'haot_faq'        => [
			[
				'q' => 'לכמה שימושים העוטפת מספיקה?',
				'a' => 'עד 100 שימושים — תלוי בעוצמת השימוש ורמת השמירה. תקבלי גם דף הסבר מפורט עם ההזמנה.',
			],
			[
				'q' => 'איך מנקים אותה?',
				'a' => 'מים קרים + סבון כלים ביד. מתייבשת מהר. אין להכניס למדיח כלים או לחשוף למים חמים.',
			],
			[
				'q' => 'האם החומרים בטוחים למגע עם מזון?',
				'a' => 'כן — 100% טבעי: OEKO-TEX 100, שעווה בתקן גרמני, שמן חוחובה ושרף אורן. ללא BPA, ללא כימיקלים.',
			],
			[
				'q' => 'יש ריח מיוחד?',
				'a' => 'יש ריח עדין של שעווה ושרף טבעי שמתמתן עם השימוש. ניתן להחזיר תוך 14 יום אם לא מרוצים.',
			],
			[
				'q' => 'לאיזה מזון לא מתאים?',
				'a' => 'לא מתאים לבשר/דגים גולמיים, למיקרוגל, לתנור ולמים חמים מעל 40°C.',
			],
			[
				'q' => 'האם ניתן לקנות ציפוי חדש?',
				'a' => 'כן — ניתן לרכוש ערכת ציפוי התחדשות בנפרד ולהאריך את חיי הבד.',
			],
		],

		/* ── How to use ── */
		'haot_how_to'     => [
			[ 'num' => '01', 'label' => 'חממי בכפות הידיים', 'sub' => 'החום מרכך את השעווה ומאפשר עיצוב' ],
			[ 'num' => '02', 'label' => 'עטפי את המזון',     'sub' => 'לחצי ועצבי את הצורה הרצויה' ],
			[ 'num' => '03', 'label' => 'אחסני במקרר',       'sub' => 'הציפוי נאטם בקור ושומר על הטריות' ],
			[ 'num' => '04', 'label' => 'שטפי וחזרי',        'sub' => 'מים קרים + סבון, ייבוש ושימוש חוזר' ],
		],
	];
}

function hf_seed( $pid ) {
	if ( ! $pid || get_post_type($pid) !== 'product' ) return false;
	foreach ( hf_seed_data() as $k => $v ) update_post_meta($pid,$k,$v);
	update_post_meta($pid,'_haotfet_template','on'); // explicit — never falls back to Elementor
	return true;
}

function hf_clear( $pid ) {
	foreach ( ['haot_subtitle','haot_rating_val','haot_rating_cnt','haot_desc','haot_shipping',
	           'haot_sizes','haot_benefits','haot_accordion','haot_faq','haot_how_to'] as $k ) {
		delete_post_meta($pid,$k);
	}
	delete_post_meta($pid,'_haotfet_template');
	return true;
}

function hf_in_stock_ids( $limit = -1 ) {
	return get_posts([
		'post_type'=>'product','post_status'=>'publish','fields'=>'ids',
		'posts_per_page'=>$limit,'orderby'=>'date','order'=>'ASC',
		'meta_query'=>[['key'=>'_stock_status','value'=>'instock']],
	]);
}

/* ══════════════════════════════════════════════════════════
   7. ADMIN MENU — overview
   ══════════════════════════════════════════════════════════ */
add_action( 'admin_menu', function () {
	add_menu_page('Haotfet Woo','Haotfet Woo','manage_options','haotfet-woo','hf_admin_page','dashicons-store',58);
} );

function hf_admin_page() {
	$msg=''; $type='success';

	if ( isset($_GET['hf_seed']) && check_admin_referer('hf_seed_action') ) {
		$pid = intval($_GET['hf_seed']);
		if ( hf_seed($pid) ) { $msg="Seeded #$pid"; } else { $msg='Failed'; $type='error'; }
	}
	if ( isset($_GET['hf_clear']) && check_admin_referer('hf_clear_action') ) {
		hf_clear(intval($_GET['hf_clear']));
		$msg='Cleared #'.intval($_GET['hf_clear']); $type='warning';
	}
	if ( isset($_GET['hf_seed_all']) && check_admin_referer('hf_seed_all_action') ) {
		$ids = hf_in_stock_ids(); $n=0;
		foreach($ids as $id){ hf_seed($id); $n++; }
		$msg="Seeded $n in-stock products.";
	}

	$in_stock  = hf_in_stock_ids();
	$out_stock = get_posts(['post_type'=>'product','post_status'=>'publish','fields'=>'ids','posts_per_page'=>-1,
		'meta_query'=>[['key'=>'_stock_status','value'=>'outofstock']]]);

	$seed_all_url = wp_nonce_url(add_query_arg(['page'=>'haotfet-woo','hf_seed_all'=>1],admin_url('admin.php')),'hf_seed_all_action');
	// Version info for update tracking
	$template_ver = '—';
	$tpl = HF_DIR . 'template-single-product.php';
	if (file_exists($tpl)) {
		$head = file_get_contents($tpl, false, null, 0, 120);
		if (preg_match('/v(\d+\.\d+(?:\.\d+)?)/', $head, $m)) $template_ver = $m[1];
	}
	?>
	<div class="wrap" style="max-width:980px">
	<div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:14px 20px;margin-bottom:20px;display:flex;align-items:center;gap:20px;flex-wrap:wrap">
		<div><strong style="font-size:15px">🌿 Haotfet Woo</strong>
			<code style="background:#f4f4f4;border-radius:4px;padding:2px 8px;font-size:12px">v<?php echo HF_VER; ?></code>
			<span style="color:#888;font-size:12px;margin-right:8px">· template <?php echo esc_html($template_ver); ?></span>
		</div>
		<div style="margin-right:auto;font-size:12px;color:#666">עדכון: העלה את שני הקבצים ב-FTP ← לחץ <strong>Seed</strong></div>
		<div style="font-size:11px;color:#999;direction:ltr">haotfet-woo.php · template-single-product.php</div>
	</div>
	<h1>Haotfet Woo</h1>
	<?php if($msg): ?><div class="notice notice-<?php echo $type;?> is-dismissible"><p><?php echo esc_html($msg);?></p></div><?php endif;?>

	<div style="display:flex;gap:16px;flex-wrap:wrap;margin:16px 0">
		<div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px 20px;text-align:center">
			<div style="font-size:2rem;font-weight:800;color:#FFC929"><?php echo count($in_stock);?></div>
			<div style="font-size:12px;color:#555">In Stock</div>
		</div>
		<div style="background:#fff;border:1px solid #ddd;border-radius:8px;padding:16px 20px;text-align:center">
			<div style="font-size:2rem;font-weight:800;color:#ccc"><?php echo count($out_stock);?></div>
			<div style="font-size:12px;color:#555">Out of Stock</div>
		</div>
	</div>

	<p>
		<a href="<?php echo esc_url($seed_all_url);?>" class="button button-primary"
			onclick="return confirm('Seed ALL in-stock products with demo data?')">⟳ Seed all in-stock products</a>
	</p>

	<h2>In-Stock Products</h2>
	<?php hf_product_table($in_stock); ?>

	<details style="margin-top:20px">
		<summary style="cursor:pointer;font-weight:600;color:#888">Out of Stock (<?php echo count($out_stock);?>) — click to expand</summary>
		<?php hf_product_table($out_stock); ?>
	</details>
	</div>
	<?php
}

function hf_product_table($ids){
	if(empty($ids)){echo '<p style="color:#888">None.</p>';return;}
	echo '<table class="widefat" style="max-width:900px"><thead><tr>
		<th>Product</th><th>Stock</th><th>subtitle</th><th>benefits</th><th>accordion</th><th>faq</th><th>how_to</th><th>template</th><th></th>
	</tr></thead><tbody>';
	foreach($ids as $pid){
		$product  = wc_get_product($pid);
		$stock    = $product ? $product->get_stock_status() : '?';
		$sub      = get_post_meta($pid,'haot_subtitle',true);
		$bens     = get_post_meta($pid,'haot_benefits',true);
		$acc      = get_post_meta($pid,'haot_accordion',true);
		$faq      = get_post_meta($pid,'haot_faq',true);
		$how      = get_post_meta($pid,'haot_how_to',true);
		$tpl      = get_post_meta($pid,'_haotfet_template',true) ?: 'auto';
		$ok='<span style="color:green">✓</span>';$no='<span style="color:#ccc">—</span>';
		$seed_url  = wp_nonce_url(add_query_arg(['page'=>'haotfet-woo','hf_seed'=>$pid],admin_url('admin.php')),'hf_seed_action');
		$clear_url = wp_nonce_url(add_query_arg(['page'=>'haotfet-woo','hf_clear'=>$pid],admin_url('admin.php')),'hf_clear_action');
		echo '<tr>';
		echo '<td><strong>'.esc_html(get_the_title($pid)).'</strong> <span style="color:#999">#'.$pid.'</span></td>';
		echo '<td><span style="color:'.($stock==='instock'?'green':'#c00').'">'.$stock.'</span></td>';
		echo '<td>'.($sub?$ok:$no).'</td>';
		echo '<td>'.(is_array($bens)?$ok.' '.count($bens):$no).'</td>';
		echo '<td>'.(is_array($acc)?$ok.' '.count($acc):$no).'</td>';
		echo '<td>'.(is_array($faq)?$ok.' '.count($faq):$no).'</td>';
		echo '<td>'.(is_array($how)?$ok.' '.count($how):$no).'</td>';
		echo '<td><code style="font-size:11px">'.$tpl.'</code></td>';
		echo '<td style="white-space:nowrap">';
		echo '<a href="'.esc_url($seed_url).'" class="button button-small">Seed</a> ';
		echo '<a href="'.esc_url($clear_url).'" class="button button-small" onclick="return confirm(\'Clear?\')">Clear</a> ';
		echo '<a href="'.esc_url(get_edit_post_link($pid)).'" class="button button-small">Edit</a> ';
		echo '<a href="'.esc_url(get_permalink($pid)).'" class="button button-small" target="_blank">View ↗</a>';
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}
