<?php
/**
 * WBolt functions and definitions
 *
 * Set up the theme and provides some helper functions, which are used in the
 * theme as custom template tags. Others are attached to action and filter
 * hooks in WordPress to change core functionality.
 *
 * When using a child theme you can override certain functions (those wrapped
 * in a function_exists() call) by defining them first in your child theme's
 * functions.php file. The child theme's functions.php file is included before
 * the parent theme's file, so the child theme functions would be used.
 *
 * @link https://codex.wordpress.org/Theme_Development
 * @link https://codex.wordpress.org/Child_Themes
 *
 * Functions that are not pluggable (not wrapped in function_exists()) are
 * instead attached to a filter or action hook.
 *
 * For more information on hooks, actions, and filters,
 * {@link https://codex.wordpress.org/Plugin_API}
 *
 * @package WordPress
 * @subpackage storeys
 * @since WBolt 1.0
 */

if ( version_compare( $GLOBALS['wp_version'], '4.4-alpha', '<' ) ) {
	require get_template_directory() . '/utils/back-compat.php';
}

require_once get_template_directory().'/version.php';

if ( ! function_exists( 'wbolt_setup' ) ) :
function wbolt_setup() {
	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	load_theme_textdomain( 'wbolt', get_template_directory() . '/languages' );

	// This theme styles the visual editor with editor-style.css to match the theme style.
	add_editor_style(get_template_directory_uri() . '/css/editor-style.css?v='.WB_ASSETS_VER);

	/*
	 * Enable support for Post Thumbnails on posts and pages.
	 *
	 * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
	 */
	add_theme_support( 'post-thumbnails' );
	set_post_thumbnail_size( 600, 600, false );

	add_theme_support( "title-tag" );
	add_theme_support( "custom-header", array() );
	add_theme_support( "custom-background", array() );

	// This theme uses wp_nav_menu() in two locations.
	register_nav_menus( array(
		'primary' => __( 'Main menu', 'wbolt' ),
		'social'  => __( 'URLs on footer', 'wbolt' ),
	) );

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
	) );

	//移除 dns-prefetch for 's.w.org'
	remove_action( 'wp_head', 'wp_resource_hints', 2 );


	//禁用REST API
    add_filter('rest_enabled', '__return_false');
    add_filter('rest_jsonp_enabled', '__return_false');

    remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );

    /**
     * Disable the emoji's
     */
    function disable_emojis() {
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
        add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
    }
    add_action( 'init', 'disable_emojis' );
    /**
     * Filter function used to remove the tinymce emoji plugin.
     */
    function disable_emojis_tinymce( $plugins ) {
        return array_diff( $plugins, array( 'wpemoji' ) );
    }

	require_once('common/class-tgm-plugin-activation.php');
}

endif; // wbolt_setup

add_action( 'after_setup_theme', 'wbolt_setup' ); //初始化


/**
 * Enqueues scripts and styles at head.
 *
 * @since WBolt 1.0
 */
if (!function_exists('wbolt_header')) {
	function wbolt_header() {
		wp_enqueue_style( 'wbolt-style', get_template_directory_uri() . '/css/style_wbolt.css', false, WB_ASSETS_VER );
		wp_enqueue_script( 'wbolt-base', get_template_directory_uri() . '/js/base.js', array('jquery'),WB_ASSETS_VER, true);
		wp_localize_script( 'wbolt-base', 'wbl', array(
			'like' => __( 'Like', 'wbolt' ),
			'searchPlaceHolder' => __( 'Please input the keywords', 'wbolt' ),
		) );
	}

	add_action( 'wp_enqueue_scripts', 'wbolt_header' );
}

//引入wbolt通用方法
require_once 'common/_wbt_core.php';


if (!function_exists('require_setting')) {
	function require_setting(){
		//load setting
		require_once 'settings/options.inc.php';
	}
	require_setting();
}

/**
 * 插入通用js变量
 */
if (!function_exists('wbolt_js_config')) {
	function wbolt_js_config(){
		$js_var = array(
			'home_url'=>home_url(),
			'theme_url'=>get_template_directory_uri(),
			'ajax_url'=>admin_url('/admin-ajax.php'),
			'theme_name' => WB_THEMES_CODE,'assets_ver'=>WB_ASSETS_VER);
		$js_var['_wp_uid'] = get_current_user_id();
		$js_var['_pid'] = get_the_ID() ? get_the_ID() : 0;

		$conf_js = "var wb_base = ".json_encode($js_var)."; ".apply_filters('wb_js_conf','')."\n";

		return $conf_js;
	}
}

if( wb_opt('gutenberg_switch') ) {
	//禁用古腾堡
	add_filter('use_block_editor_for_post_type',function($is_user,$post_type){return false;},10,2);

	//WordPress 5.0+移除 block-library CSS
	add_action( 'wp_enqueue_scripts', 'tonjay_remove_block_library_css', 100 );
	function tonjay_remove_block_library_css() {
		wp_dequeue_style( 'wp-block-library' );
	}
}

//搜索只显示post
function search_filter_page($query) {
	if ($query->is_search) {
		$query->set('post_type', 'post');
	}
	return $query;
}
add_filter('pre_get_posts','search_filter_page');

require_once 'widget/widget.php';
require_once 'utils/related_post/related_post.inc.php';

//主题激活时默认创建page
require_once 'utils/wb_default_page/def_page.inc.php';

//首页
require_once 'utils/tabs_display_post/tabs_display_post.php';

//相册
if( wb_opt('gallery_switch') == null ) {
	require_once 'utils/wb_gallery/wb_gallery.inc.php';
}


//建议插件
add_action( 'tgmpa_register', 'wbolt_register_required_plugins' );

/**
 * Register the required plugins for this theme.
 *
 * In this example, we register five plugins:
 * - one included with the TGMPA library
 * - two from an external source, one from an arbitrary source, one from a GitHub repository
 * - two from the .org repo, where one demonstrates the use of the `is_callable` argument
 *
 * The variables passed to the `tgmpa()` function should be:
 * - an array of plugin arrays;
 * - optionally a configuration array.
 * If you are not changing anything in the configuration array, you can remove the array and remove the
 * variable from the function call: `tgmpa( $plugins );`.
 * In that case, the TGMPA default settings will be used.
 *
 * This function is hooked into `tgmpa_register`, which is fired on the WP `init` action on priority 10.
 */
function wbolt_register_required_plugins() {
	$plugins = array(
		array(
			'name'         => 'WP资源下载管理', // The plugin name.
			'slug'         => 'download-info-page', // The plugin slug (typically the folder name).
			'source'       => 'https://downloads.wordpress.org/plugin/download-info-page.zip', // The plugin source.
			'required'     => true, // If false, the plugin is only 'recommended' instead of required.
			'external_url' => 'https://wordpress.org/plugins/download-info-page/', // If set, overrides default API URL and points to an external URL.
		),
		array(
			'name'         => 'Smart SEO Tool', // The plugin name.
			'slug'         => 'smart-seo-tool', // The plugin slug (typically the folder name).
			'source'       => 'https://downloads.wordpress.org/plugin/smart-seo-tool.zip', // The plugin source.
			'required'     => false, // If false, the plugin is only 'recommended' instead of required.
			'external_url' => 'https://wordpress.org/plugins/smart-seo-tool/', // If set, overrides default API URL and points to an external URL.
		),
		array(
			'name'         => '百度搜索推送管理',
			'slug'         => 'baidu-submit-link',
			'source'       => 'https://downloads.wordpress.org/plugin/baidu-submit-link.zip',
			'required'     => false,
			'external_url' => 'https://wordpress.org/plugins/baidu-submit-link/',
		),
		array(
			'name'         => '打赏/点赞/分享组件',
			'slug'         => 'donate-with-qrcode',
			'source'       => 'https://downloads.wordpress.org/plugin/donate-with-qrcode.zip',
			'required'     => false,
			'external_url' => 'https://wordpress.org/plugins/donate-with-qrcode/',
		),
	);

	$config = array(
		'id'           => 'wbolt',                 // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'tgmpa-install-plugins', // Menu slug.
		'parent_slug'  => 'options-wbthemes',            // Parent menu slug.
		'capability'   => 'edit_theme_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => true,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => false,                   // Automatically activate plugins after installation or not.
		'message'      => '',                      // Message to output right before the plugins table.

	);

	tgmpa( $plugins, $config );
}


//部分内容输入密码可见
function e_secret($atts, $content=null){
	 extract(shortcode_atts(array('key'=>null), $atts));
	 if(isset($_POST['e_secret_key']) && $_POST['e_secret_key'] !='' && $_POST['e_secret_key']==$key){
		return '
				<div class="e-secret">'.$content.'XXXX</div>
				';
	 }elseif (isset($_POST['e_secret_key']) && $_POST['e_secret_key'] !='' && $key =='' && $_POST['e_secret_key']=='000666') {
		  return '
				<div class="e-secret">'.$content.'LLL</div>
				';
	 }
	 else{
		 return '
		<form class="e-secret" action="'.get_permalink().'" method="post" name="e-secret" >
			<div class="e-secret-container">
			<div class="e-secret-title-content" >此处内容已经被作者无情的隐藏，请输入验证码查看内容：</div>
			<div class="e-secret-code" >
    			<span>验证码：</span>
			<input type="password" name="e_secret_key" class="euc-y-i" maxlength="50" >
			<input type="submit" class="euc-y-s" value="确定" >
    		</div>
			<div class="e-secret-tip" >
			    请关注本站公众号回复“<span>验证码</span>”，获取验证码。
			    <span>【注】</span>”在微信里搜索“不会笑青年”或者“laughyouth”或者微信扫描右侧二维码都可以关注微信公众号。
			</div>
			</div>
			<img src="http://www.ityouknow.com/assets/images/cartoon.jpg" alt="不会笑青年" >
			</form>
		';
	 }
}

add_shortcode('secret','e_secret');