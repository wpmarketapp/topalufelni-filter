<?php
/**
 * Plugin Name: TopAlufelni Filter
 * Plugin URI: https://pusztaizsomborr@gmail.com
 * Description: Autó alapján szűrhető felni kereső plugin a Wheel Size API integrációval
 * Version: 1.0.0
 * Author: Pusztai Zsombor
 * Author URI: https://pusztaizsomborr@gmail.com
 * Text Domain: topalufelni-filter
 */

// Biztonsági ellenőrzés
if (!defined('ABSPATH')) {
    exit;
}

// Plugin konstansok definiálása
define('TAF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TAF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TAF_API_KEY', '799df303407dda0afc6f7fe446f536fa');

// Osztályok betöltése
require_once TAF_PLUGIN_DIR . 'includes/class-taf-api.php';
require_once TAF_PLUGIN_DIR . 'includes/class-taf-shortcode.php';

// Plugin aktiválási hook
register_activation_hook(__FILE__, 'taf_activate_plugin');

function taf_activate_plugin() {
    // Aktiválási logika
    if (!get_option('taf_plugin_settings')) {
        add_option('taf_plugin_settings', array(
            'api_key' => TAF_API_KEY,
            'cache_time' => 86400 // 24 óra
        ));
    }
}

// Plugin inicializálása
function taf_init() {
    // API osztály példányosítása
    $api = new TAF_API();
    
    // Shortcode osztály példányosítása
    new TAF_Shortcode($api);
    
    // Stílusok és szkriptek betöltése
    add_action('wp_enqueue_scripts', 'taf_enqueue_scripts');
}
add_action('init', 'taf_init');

// Stílusok és szkriptek betöltése
function taf_enqueue_scripts() {
    wp_enqueue_style('taf-styles', TAF_PLUGIN_URL . 'assets/css/style.css', array(), '1.0.0');
    wp_enqueue_script('taf-script', TAF_PLUGIN_URL . 'assets/js/script.js', array('jquery'), '1.0.0', true);
    
    // AJAX URL átadása a JavaScript számára
    wp_localize_script('taf-script', 'tafAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('taf-ajax-nonce')
    ));
}

// Admin menü hozzáadása
function taf_admin_menu() {
    add_menu_page(
        'TopAlufelni Filter',
        'TopAlufelni Filter',
        'manage_options',
        'topalufelni-filter',
        'taf_admin_page',
        'dashicons-car',
        30
    );
}
add_action('admin_menu', 'taf_admin_menu');

// Admin oldal megjelenítése
function taf_admin_page() {
    include TAF_PLUGIN_DIR . 'admin/admin-page.php';
} 