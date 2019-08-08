<?php
/**
 * @package Blue Billywig
 * @version 0.1
 */
/*
Plugin Name: Blue Billywig
Plugin URI: https://github.com/DaanRuiter/BB_wordpress_plugin
Description: Allows for easier embedding of mediaclips and playlists
Author: Daan Ruiter
Version: 0.1
Author URI: http://daanruiter.net/
*/

define('BB_PLUGIN_VERSION', '0.1');
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

require_once BB_PLUGIN_INC . "strings.php";
require_once BB_PLUGIN_INC . "content.php";
require_once BB_PLUGIN_INC . 'rpcPackage.php';
use BlueBillywig\VMSRPC\RPC;

class BlueBillywig
{
	private static $instance;

	private $options;
	private $rpc;

	//Singleton
	public static function instance(){
		if ( !self::$instance ) self::$instance = new BlueBillywig();
		return self::$instance;
	}

	public function __construct(){
		register_activation_hook( __FILE__, 'acivate_bb_plugin' );
		register_deactivation_hook( __FILE__, 'deacivate_bb_plugin' );
		register_uninstall_hook( __FILE__, 'uninstall_bb_plugin' );
		
		$this->load_options();

		self::$instance = $this;
	}

	//Register Wordpress menus
	public function register_menus() {
		add_menu_page( 'Blue Billywig', 'Blue Billywig', 'manage_options', 'bb-plugin', 'plugin_home', BB_PLUGIN_IMG . 'icon.png' );
		add_submenu_page( 'bb-plugin', 'Generate shortcode', 'Generate shortcode', 'manage_options', 'bb-shortcode', 'plugin_shortcode' );
	}

	//Update the shared secret of the RPC when the API settings were updated
	public function update_rpc(){
		if(!$this->rpc){ return; }
		$this->rpc->host = 'https://' . $this->options[BB_API_SETTINGS_PUBLICATION] . '.bbvms.com';
		$this->rpc->sharedSecret = $this->options[BB_API_SETTINGS_ID] . '-' . $this->options[BB_API_SETTINGS_SECRET];
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
			$this->create_rpc($this->options[BB_API_SETTINGS_PUBLICATION], $this->options[BB_API_SETTINGS_SECRET], $this->options[BB_API_SETTINGS_ID]);
		}	
		return $this->rpc;
	}
	
	//Test API key values by trying to retreive the publication info
	public function test_api_key($publication, $secret, $id){
		$publicationInfo = $this->get_rpc()->xml('publication');
		if(!$publicationInfo){
			return false;
		}
		return stripos($publicationInfo, 'Invalid token') === false;
	}

	//Test the stored API key by trying to retreive the publication info
	public function test_stored_api_key(){
		return $this->test_api_key($this->options[BB_API_SETTINGS_PUBLICATION], $this->options[BB_API_SETTINGS_SECRET], $this->options[BB_API_SETTINGS_ID]);
	}

	//Ensure the plugins option are known to Wordpress
	public function ensure_options(){
		$options = get_option(BB_API_SETTINGS_GROUP);
		
		if(!$options){
			add_option(BB_API_SETTINGS_GROUP, BB_API_SETTINGS_DEFAULT);
		}else if (is_array($options) && count($options) !== count(BB_API_SETTINGS_DEFAULT)){
			update_option(BB_API_SETTINGS_GROUP, BB_API_SETTINGS_DEFAULT);
		}
	}

	//Load the plugin's options from Wordpress
	public function load_options(){
		$this->ensure_options();
		$this->options = get_option(BB_API_SETTINGS_GROUP);
	}

	//Get the plugin's options
	public function get_options(){
		$this->ensure_options();
		return $this->options;
	}

	//Save the plugin's options in Wordpress
	public function save_options($data){
        update_option(BB_API_SETTINGS_GROUP, $data);
		$this->load_options();
		$this->update_rpc();
	}

	//Delete the plugin's options from Wordpress
	function delete_options(){
		delete_option(BB_API_SETTINGS_GROUP);
	}

	private function acivate_bb_plugin(){
		ensure_options();
	}
	private function deacivate_bb_plugin(){

	}	
	private function uninstall_bb_plugin(){
		$this->delete_options();
	}
}

//Initialize the plugin
function init_bb_plugin(){
	$bb = new BlueBillywig();
	$bb->register_menus();

    $bbOptions = BlueBillywig::instance()->get_options();
	$bb->create_rpc($bbOptions[BB_API_SETTINGS_PUBLICATION], $bbOptions[BB_API_SETTINGS_SECRET], $bbOptions[BB_API_SETTINGS_ID]);
}

//Plugin Menu > Bluebillywig path
function plugin_home(){
	echo '<br/><img src="' . BB_PLUGIN_IMG . 'logo.png">';
	echo '<h4 style="display:inline-block;padding-left:10px">Version ' . BB_PLUGIN_VERSION . '</h4>';
	require_once BB_PLUGIN_PAGES . 'settings.php';
}

//Plugin Menu > Generate shortcode path
function plugin_shortcode(){
	//Load JS
    $bbOptions = BlueBillywig::instance()->get_options();
	wp_enqueue_script('bb-mediaclip-library', BB_PLUGIN_JS . 'bbMediaclipLibrary.js');
	wp_localize_script('bb-mediaclip-library', 'defaultPlayout', $bbOptions[BB_API_SETTINGS_DEFAULT_PLAYOUT]);
	wp_localize_script('bb-mediaclip-library', 'BB_STRINGS', BB_SHORTCODE_STRINGS);
	wp_localize_script('bb-mediaclip-library', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
	
	//Load css
	wp_enqueue_style('bb-shortcode-style', BB_PLUGIN_CSS . 'bbShortcode.css');

	require_once BB_PLUGIN_PAGES . 'shortcode.php';
}

//Register the Gutenberg editor embed block
function register_bb_mediaclip_block() {
	//Register js
    wp_register_script('bb-mediaclip-block', BB_PLUGIN_JS . 'bbVideoBlock.js',
        array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' )
	);

	//Localize API-options & ajaxURL
	$bbOptions = BlueBillywig::instance()->get_options();
	
	wp_localize_script('bb-mediaclip-block', 'publication', $bbOptions[BB_API_SETTINGS_PUBLICATION]);
	wp_localize_script('bb-mediaclip-block', 'allPlayouts', fetch_all_playouts());
	wp_localize_script('bb-mediaclip-block', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
	wp_localize_script('bb-mediaclip-block', 'BB_STRINGS', BB_BLOCK_STRINGS );

	wp_enqueue_style('bb-mediaclip-block-style', BB_PLUGIN_CSS . 'bbVideoBlock.css');

	//Register the block
    register_block_type( 'bb-plugin/bb-mediaclip-block', array(
        'editor_script' => 'bb-mediaclip-block',
    ) );
	
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
add_action('admin_head', 'add_mce_widget');

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

add_action( 'admin_menu', 'init_bb_plugin' );
add_action( 'init', 'register_bb_mediaclip_block' ); 
add_action( 'wp_ajax_search_videos_request', 'search_videos_request' );
add_action( 'wp_ajax_search_playouts_request', 'search_playouts_request' );
add_action( 'wp_ajax_search_single_video_request', 'search_single_video_request' );
?>