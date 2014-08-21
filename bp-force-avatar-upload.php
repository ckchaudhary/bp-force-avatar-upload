<?php
/**
 * Plugin Name: BP Force Avatar Upload
 * Plugin URI:  https://github.com/ckchaudhary/bp-force-avatar-upload
 * Version: 0.1
 * Author: ckchaudhary
 * Author URI: http://webdeveloperswall.com
 * Description: Force your members to upload their avatar, if they have not done so already
 * Text Domain: bp-force-avatar-upload
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

add_action('bp_loaded', array('BP_Force_Avatar_Helper', 'get_instance'));

class BP_Force_Avatar_Helper {

	private static $instance;

	private function __construct() {
		//load text domain
		$this->load_textdomain();
		add_action( 'init', array( $this, 'load_components' ) );
	}

	public static function get_instance() {

		if (!isset(self::$instance))
			self::$instance = new self();

		return self::$instance;
	}

	/**
	 * Load plugin textdomain for translation
	 */
	public function load_textdomain() {
		$domain = 'bp-force-avatar-upload';
		$locale = apply_filters('plugin_locale', get_locale(), $domain);

		//first try to load from wp-contents/languages/plugins/ directory
		load_textdomain($domain, WP_LANG_DIR . '/plugins/' . $domain . '-' . $locale . '.mo');

		//if not found, then load from plugin's languages directory
		load_plugin_textdomain($domain, false, $this->lang_dir);
	}

	public function load_components() {
		$load_pluing = false;
		
		if ( is_user_logged_in() && $this->user_has_avatar()==false ) {
			$load_pluing = true;
		}
		
		$load_pluing = apply_filters( 'bp_force_avatar_upload', $load_pluing );
		
		if( $load_pluing ){
			include_once dirname( __FILE__ ) . '/emi-file-upload.php';
			
			add_action(	'wp_footer',					array( $this, 'show_form'));
			add_action( 'wp_enqueue_scripts',			array( $this, 'load_css_js' ) );
			add_action( 'wp_ajax_hsfa_submit_avatar',	array( $this, 'finish_upload' ) );
		}
	}

	function user_has_avatar($user_id = false) {
		if (!$user_id) {
			$user_id = bp_loggedin_user_id();
		}

		if (bp_core_fetch_avatar(array('item_id' => $user_id, 'no_grav' => true, 'html' => false)) != bp_core_avatar_default('local')) {
			return true;
		}

		return false;
	}

	function load_css_js(){
		$base_url = WP_PLUGIN_URL .'/'. basename( dirname( __FILE__ ) );
		wp_enqueue_script(	'fancybox',			$base_url . '/assets/jquery.fancybox.pack.js', array('jquery'));
		wp_enqueue_style(	'fancybox',			$base_url . '/assets/jquery.fancybox.css' );
		
		wp_enqueue_script(	'hs_force_avatar',	$base_url . '/assets/script.js', array('jquery', 'fancybox'));
		
		$data = array(
			'ajaxurl'	=> admin_url('/admin-ajax.php'),
		);
		
		wp_enqueue_style( 'jcrop' );
        wp_enqueue_script( 'jcrop', array( 'jquery' ) );
        add_action( 'wp_head', 'bp_core_add_cropper_inline_css' );
		
		wp_localize_script('hs_force_avatar', 'HSFA__', $data);
	}
	
	function show_form() {
		?>
		<div style="display: none">
			<div id = "signup-avatar-wrapper">
				<form method="POST" id="frm_hsfa_upload_avatar" action="<?php echo admin_url('/admin-ajax.php');?>">
					<h3><?php _e( 'Please upload an avatar', 'bp-force-avatar-upload' );?></h3>
					
					<?php echo do_shortcode( '[emi_fu_component class="user-dp" attachment="no"]' );?>
					
					<p id="crop_image" style="display: none">
						<label><?php _e('Crop image','bp-force-avatar-upload'); ?></label>
						<img src="" id="hsfc_uploaded_avatar"/>
					</p>
					<p class="response"></p>
					<?php wp_nonce_field('bp_avatar_upload') ?>
					<input type="hidden" name="image_src" id="image_src" value="" />
					<input type="hidden" name="action" value="hsfa_submit_avatar" />
					<input type="hidden" id="x" name="x" />
					<input type="hidden" id="y" name="y" />
					<input type="hidden" id="w" name="w" />
					<input type="hidden" id="h" name="h" />
				</form>

			</div>
		</div>
		<?php 
	}
	
	function finish_upload(){
		check_ajax_referer( 'bp_avatar_upload' );
		$retval = array( 'status' => false, 'message' => '' );
		
		$avatar_to_crop = str_replace(bp_core_avatar_url(), '', $_POST['image_src'] );
		
		$args = array(
			'item_id'       => bp_loggedin_user_id(),
			'original_file' => $avatar_to_crop,
			'crop_x'        => $_POST['x'],
			'crop_y'        => $_POST['y'],
			'crop_w'        => $_POST['w'],
			'crop_h'        => $_POST['h']
		);

		if ( ! bp_core_avatar_handle_crop( $args ) ) {
			$retval['message'] = __( 'There was a problem cropping your avatar.', 'bp-force-avatar-upload' );
		} else {
			do_action( 'xprofile_avatar_uploaded' );
			$retval['status'] = true;
			$retval['message'] = __( 'Your new avatar was uploaded successfully.', 'bp-force-avatar-upload' );
			$retval['redirect'] = bp_loggedin_user_domain();
		}
		
		die( json_encode( $retval ) );
	}
}