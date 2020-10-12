<?php
    // This makes sure certain Wordpress functions exist when testing the output of the plugin
    // Useful when testing the behaviour on plugin activation

    if(!function_exists('plugin_dir_url')){
        function plugin_dir_url($plugin){
            return dirname( __FILE__ );
        }
    }
    if(!function_exists('register_activation_hook')){
        function register_activation_hook($a, $b){}
    }
    if(!function_exists('register_deactivation_hook')){
        function register_deactivation_hook($a, $b){}
    }
    if(!function_exists('register_uninstall_hook')){
        function register_uninstall_hook($a, $b){}
    }
    if(!function_exists('get_option')){
        function get_option($a){ return 'option'; }
    }
    if(!function_exists('is_plugin_active')){
        function is_plugin_active($a){ return false; }
    }

?>