<?php
/**
 * @package Blue Billywig
 * @version 0.3.0
 */
/*
Plugin Name: Blue Billywig
Plugin URI: https://github.com/DaanRuiter/BB_wordpress_plugin
Description: Allows for easier embedding of mediaclips and playlists
Author: Daan Ruiter
Version: 0.3.0
Author URI: http://daanruiter.net/
*/

// Include wp interface when testing plugin output
// require_once(dirname( __FILE__ ) . '/inc/wpInterface.php');

define('BB_PLUGIN_VERSION', '0.3.0');
define('BB_PLUGIN_BETA', true);
define('BB_PLUGIN_DIR', dirname( __FILE__ ));
define('BB_PLUGIN_IMG', plugin_dir_url( __FILE__ ) .'/img/');
define('BB_PLUGIN_JS',  plugin_dir_url( __FILE__ ) . '/js/');
define('BB_PLUGIN_CSS',  plugin_dir_url( __FILE__ ) . '/css/');
define('BB_PLUGIN_INC', BB_PLUGIN_DIR . '/inc/');
define('BB_PLUGIN_LIB', BB_PLUGIN_DIR . '/lib/');
define('BB_PLUGIN_PAGES', BB_PLUGIN_DIR . '/pages/');
define('BB_API_SETTINGS_GROUP', 'bb-api-settings');
define('BB_API_SETTINGS_PUBLICATION', 'bb-api-publication');
define('BB_API_SETTINGS_SECRET', 'bb-api-secret');
define('BB_API_SETTINGS_DEFAULT_PLAYOUT', 'bb-api-defaultPlayout');
define('BB_API_SETTINGS_ID', 'bb-api-id');
define('BB_API_SETTINGS_DEFAULT', array(BB_API_SETTINGS_PUBLICATION => "", 
										BB_API_SETTINGS_SECRET => "", 
										BB_API_SETTINGS_ID => 0,
										BB_API_SETTINGS_DEFAULT_PLAYOUT => "default"));
define('BB_PLUGIN_SETTING_SUPPRESS_NOTICES', 'bb-plugin-suppress-notices');
define('BB_PLUGIN_SETTING_AUTOPLAY', 'bb-plugin-autoplay');
define('BB_PLUGIN_SETTING_USE_IFRAME_EMBED', 'bb-plugin-iframe-embed');
define('BB_PLUGIN_SETTING_AUTOPUBLISH', 'bb-plugin-autopublish');
define('BB_PLUGIN_SETTINGS_DEFAULT', array(	BB_PLUGIN_SETTING_SUPPRESS_NOTICES => 'false',
											BB_PLUGIN_SETTING_AUTOPLAY => 'false',
											BB_PLUGIN_SETTING_AUTOPUBLISH => 'true',
											BB_PLUGIN_SETTING_USE_IFRAME_EMBED => 'false'));

require_once BB_PLUGIN_INC . 'uploader.php';
require_once BB_PLUGIN_INC . "strings.php";
require_once BB_PLUGIN_INC . "content.php";
require_once BB_PLUGIN_INC . 'rpcPackage.php';
require_once BB_PLUGIN_INC . 'util.php';
require_once BB_PLUGIN_INC . 'classes/mediaclip.class.php';
use BlueBillywig\VMSRPC\RPC;

class BlueBillywig
{
	private static $instance;

	private $apiOptions;
	private $pluginOptions;
	private $rpc;

	//Singleton
	public static function instance(){
		if ( !self::$instance ){ self::$instance = new BlueBillywig(); error_log('Created instance'); }
		return self::$instance;
	}

	public static function acivate_bb_plugin(){
		self::instance()->ensure_api_options();
	}
	public static function deacivate_bb_plugin(){

	}	
	public static function uninstall_bb_plugin(){
		self::instance()->delete_options();
	}

	public function __construct(){
		register_activation_hook( __FILE__, array('BlueBillywig', 'acivate_bb_plugin') );
		register_deactivation_hook( __FILE__, array('BlueBillywig', 'deacivate_bb_plugin') );
		register_uninstall_hook( __FILE__, array('BlueBillywig', 'uninstall_bb_plugin') );
		
		$this->load_api_options();

		self::$instance = $this;
	}

	//Update the shared secret of the RPC when the API settings were updated
	public function update_rpc(){
		if(!$this->rpc){ return; }
		$this->rpc->host = 'https://' . $this->apiOptions[BB_API_SETTINGS_PUBLICATION] . '.bbvms.com';
		$this->rpc->sharedSecret = $this->apiOptions[BB_API_SETTINGS_ID] . '-' . $this->apiOptions[BB_API_SETTINGS_SECRET];
	}
	
	//Create our RPC
	public function create_rpc($publication, $secret, $id){
		$host = 'https://' . $publication . '.bbvms.com';
		$sharedSecret = $id . '-' . $secret;

		$this->rpc = new RPC($host, null, null, $sharedSecret);
	}

	//Retreive our RPC
	public function get_rpc(){
		if( $this->rpc == null){
			$this->create_rpc(	$this->apiOptions[BB_API_SETTINGS_PUBLICATION], 
								$this->apiOptions[BB_API_SETTINGS_SECRET], 
								$this->apiOptions[BB_API_SETTINGS_ID]);
		}	
		return $this->rpc;
	}
	
	//Test the stored API key by trying to retreive the publication info
	public function test_stored_api_key(){
		// Try to fetch a single playout (there is always at least one due to there being a default playout)
		$parameters['filterset'] = '[{"filters":[{"type":"playout"}]}]';
		$parameters['limit'] = 1;
		$playouts = BlueBillywig::get_rpc()->sapi('playout', null, 'GET', null, null, $parameters);
		
		if(!$playouts){
			return false;
		}
		
		return stripos($playouts, 'Invalid token') === false;
	}

	//Ensure the plugins option are known to Wordpress
	public function ensure_api_options(){
		$apiOptions = get_option(BB_API_SETTINGS_GROUP);

		if(!$apiOptions){
			add_option(BB_API_SETTINGS_GROUP, BB_API_SETTINGS_DEFAULT);
		}else if (is_array($apiOptions) && count($apiOptions) !== count(BB_API_SETTINGS_DEFAULT)){
			update_option(BB_API_SETTINGS_GROUP, BB_API_SETTINGS_DEFAULT);
		}
	}

	public function ensure_plugin_option($optionName){
		$pluginOption = get_option($optionName);

		if( !isset($pluginOption) || $pluginOption == NULL ){
			add_option($optionName, BB_PLUGIN_SETTINGS_DEFAULT[$optionName]);
			return BB_PLUGIN_SETTINGS_DEFAULT[$optionName];
		}
		return $pluginOption;
	}

	//Load the plugin's options from Wordpress
	public function load_api_options(){
		$this->ensure_api_options();
		$this->apiOptions = get_option(BB_API_SETTINGS_GROUP);
	}

	//Get the plugin's options
	public function get_api_options(){
		$this->ensure_api_options();
		return $this->apiOptions;
	}
	public function get_plugin_option($option){
		$value = $this->ensure_plugin_option($option);
		
		// return bools as a boolean instead of string
		if($value === 'false')
			return false;
		if($value === 'true')
			return true;

		return $value;
	}

	//Save the plugin's options in Wordpress
	public function save_api_options($data){
        update_option(BB_API_SETTINGS_GROUP, $data);
		$this->load_api_options();
		$this->update_rpc();
	}
	public function save_plugin_option($option, $value){
        update_option($option, $value);
	}

	//Delete the plugin's options from Wordpress
	function delete_options(){
		delete_option(BB_API_SETTINGS_GROUP);
	}
}

//Plugin Menu > Bluebillywig path
function page_home(){
	echo '<br/><img class="bb-logo-large" style="width:35%;" src="' . BB_PLUGIN_IMG . 'logo.png">';
	echo '<span style="display:inline-block;padding-left:10px">Version ' . BB_PLUGIN_VERSION;

	if(BB_PLUGIN_BETA){
		 echo ' <b>BETA</b>';
	}
	echo '</span>';

	wp_enqueue_script('bb-settings-script', BB_PLUGIN_JS . 'bbSettings.js');

	wp_enqueue_style('bb-shortcode-style', BB_PLUGIN_CSS . 'bbSettings.css');

	require_once BB_PLUGIN_PAGES . 'settings.php';
}

//Plugin Menu > Generate shortcode path
function page_shortcode(){
	//Load JS
    $bbAPIOptions = BlueBillywig::instance()->get_api_options();
	$autoplay = BlueBillywig::instance()->get_plugin_option(BB_PLUGIN_SETTING_AUTOPLAY) ? 'true' : 'false';

	wp_enqueue_script('bb-mediaclip-library', BB_PLUGIN_JS . 'bbMediaclipLibrary.js');
	wp_enqueue_script('bb-mediaclip-shortcode', BB_PLUGIN_JS . 'bbShortcodeGenerator.js');	

	wp_localize_script('bb-mediaclip-shortcode', 'defaultAutoplay', $autoplay);
	wp_localize_script('bb-mediaclip-shortcode', 'defaultPlayout', $bbAPIOptions[BB_API_SETTINGS_DEFAULT_PLAYOUT]);
	wp_localize_script('bb-mediaclip-library', 'BB_STRINGS', BB_SHORTCODE_STRINGS);
	wp_localize_script('bb-mediaclip-library', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
	
	//Load css
	wp_enqueue_style('bb-mediaclip-block-style', BB_PLUGIN_CSS . 'bbVideoBlock.css');
	wp_enqueue_style('bb-shortcode-style', BB_PLUGIN_CSS . 'bbShortcode.css');

	require_once BB_PLUGIN_PAGES . 'shortcode.php';
}

function page_upload(){
	$bbAPIOptions = BlueBillywig::instance()->get_api_options();
	
	wp_enqueue_script('aws-sdk', 'https://sdk.amazonaws.com/js/aws-sdk-2.283.1.min.js'); //TODO: dl & include script
	wp_enqueue_script('fine-uploader', BB_PLUGIN_JS . 'lib/fineUploader.modified.js');
	wp_enqueue_script('bb-upload-script', BB_PLUGIN_JS . 'bbUpload.js', array('fine-uploader'));

	wp_localize_script('bb-upload-script', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
	wp_localize_script('bb-upload-script', 'publicationStub', $bbAPIOptions[BB_API_SETTINGS_PUBLICATION]);

	wp_enqueue_style('bb-shortcode-style', BB_PLUGIN_CSS . 'bbSettings.css');
	wp_enqueue_style('bb-upload-style', BB_PLUGIN_CSS . 'bbUpload.css');
	
	require_once BB_PLUGIN_PAGES . 'upload.php';
}

function page_media_library(){

	wp_enqueue_style('bb-mediaclip-block-style', BB_PLUGIN_CSS . 'bbVideoBlock.css');
	wp_enqueue_style('bb-media-library-style', BB_PLUGIN_CSS . 'bbMediaLibrary.css');
	wp_enqueue_style('bb-settings-style', BB_PLUGIN_CSS . 'bbSettings.css');
	require_once BB_PLUGIN_PAGES . 'mediaLibrary.php';
}

//Register Wordpress menus
function register_menus() {

	add_menu_page( 'Blue Billywig', 'Blue Billywig', 'manage_options', 'bb-plugin', 'page_home', BB_PLUGIN_IMG . 'icon.png' );
	add_submenu_page( 'bb-plugin', 'Settings', 'Settings', 'manage_options', 'bb-plugin');
	add_submenu_page( 'bb-plugin', 'Media Library', 'Media Library', 'manage_options', 'bb-library', 'page_media_library' );
	add_submenu_page( 'bb-plugin', 'Upload Mediaclip', 'Upload Mediaclip', 'manage_options', 'bb-upload', 'page_upload' );
	add_submenu_page( 'bb-plugin', 'Generate Shortcode', 'Generate Shortcode', 'manage_options', 'bb-shortcode', 'page_shortcode' );

	if( is_plugin_active(get_top_level_plugin_dir_name(dirname(__FILE__)) . '/bluebillywig.php') && 
		is_on_plugins_page() && !BlueBillywig::instance()->test_stored_api_key()) {
		plugin_needs_configuration_notice();
	}
}

//Register the Gutenberg editor embed block
function register_bb_mediaclip_block() {
	//Register js
    wp_register_script('bb-mediaclip-block', BB_PLUGIN_JS . 'bbVideoBlock.js',
        array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' )
	);

	//Register the block
    register_block_type( 'bb-plugin/bb-mediaclip-block', array(
        'editor_script' => 'bb-mediaclip-block',
	) );
	add_action('enqueue_block_assets', 'register_bb_mediaclip_block_assets');
}

function register_bb_mediaclip_block_assets(){
	//Localize API-options & ajaxURL
	$bbAPIOptions = BlueBillywig::instance()->get_api_options();
	
	wp_localize_script('bb-mediaclip-block', 'publication', $bbAPIOptions[BB_API_SETTINGS_PUBLICATION]);
	wp_localize_script('bb-mediaclip-block', 'allPlayouts', fetch_all_playouts());
	wp_localize_script('bb-mediaclip-block', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
	wp_localize_script('bb-mediaclip-block', 'BB_STRINGS', BB_BLOCK_STRINGS );

	wp_enqueue_style('bb-mediaclip-block-style', BB_PLUGIN_CSS . 'bbVideoBlock.css');
}

//Register the TinyMCE embed widget
function add_mce_widget() {
	if (!current_user_can('edit_posts') && !current_user_can('edit_pages') ) {
		return;
	}
	
	if (get_user_option('rich_editing') == true) {
		add_filter( 'mce_external_plugins', 'add_mce_widget_script' );
		add_filter( 'mce_buttons', 'add_mce_widget_button' );
	}
}

//Register new button in the TinyMCE editor
function add_mce_widget_button( $buttons ) {
	array_push( $buttons, 'bb_mce_mediaclip' );
	return $buttons;
}

//Add the TinyMCE widget script and localize it's needed values
function add_mce_widget_script( $plugin_array ) {
	$plugin_array['bb_mce_mediaclip'] = BB_PLUGIN_JS . 'bbMediaclipWidget.js';
	$playouts = fetch_all_playouts();

	?>
	<script> //localize data for BB widget script
		var iconURL = '<?php echo BB_PLUGIN_IMG . "icon.png"; ?>';
		var playouts = '<?php echo $playouts; ?>';
		var ajaxURL = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
		var BB_STRINGS = <?php echo json_encode(BB_WIDGET_STRINGS); ?>;
	</script>
	<style>
		.mce-bb-widget-no-video-selected{color:red !important}
	</style>
	<?php
	return $plugin_array;
}

function plugin_needs_configuration_notice(){
	?>
		<div class="notice notice-error is-dismissible">
			<p>Before you can start using the <span class="bb-plugin-name-notice">
				<img src='<?PHP echo BB_PLUGIN_IMG . 'icon.png' ?>'> Blue Billywig plugin</span> it needs to be 
				<strong><a href="./admin.php?page=bb-plugin">configured</a></strong>.</p>
		</div>
		<style>
			.bb-plugin-name-notice{
				background-color: #7AB1DF;
				color: white;
				padding: 3px 5px;
				padding-top: 4px;
				border-radius: 10px;
			}
			.bb-plugin-name-notice img{
				background-color: white;
				border-radius: 5px;
			}
		</style>
	<?php
}

add_action( 'admin_head', 'add_mce_widget');
add_action( 'admin_menu', 'register_menus' );
add_action( 'init', 'register_bb_mediaclip_block' ); 
add_action( 'wp_ajax_search_videos_request', 'search_videos_request' );
add_action( 'wp_ajax_search_playouts_request', 'search_playouts_request' );
add_action( 'wp_ajax_search_single_video_request', 'search_single_video_request' );
add_action( 'wp_ajax_create_media_clip_request', 'create_media_clip_request' );
add_action( 'wp_ajax_fetch_upload_endpoint_request', 'fetch_upload_endpoint_request' );
add_shortcode( 'bbmediaclip', 'bb_mediaclip_shortcode' );

//Initialize plugin
if(class_exists("BlueBillywig")){
	$bb = new BlueBillywig();
}
?>