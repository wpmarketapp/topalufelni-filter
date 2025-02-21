<?php
/**
 * Plugin Name: Auto Felni Szuro
 * Plugin URI: https://pusztaizsomborr@gmail.com
 * Description: Autó alapján szűrhető felni kereső plugin a Wheel Size API integrációval
 * Version: 1.0.0
 * Author: Pusztai Zsombor
 * Author URI: https://pusztaizsomborr@gmail.com
 * Text Domain: auto-felni-szuro
 */

// Biztonsági ellenőrzés
if (!defined('ABSPATH')) {
    exit;
}

// Plugin konstansok definiálása
define('AFS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AFS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AFS_API_KEY', '799df303407dda0afc6f7fe446f536fa');

// Osztályok betöltése
require_once AFS_PLUGIN_DIR . 'includes/class-afs-api.php';
require_once AFS_PLUGIN_DIR . 'includes/class-afs-shortcode.php';

// Plugin aktiválási hook
register_activation_hook(__FILE__, 'afs_activate_plugin');

function afs_activate_plugin() {
    // Aktiválási logika
    if (!get_option('afs_plugin_settings')) {
        add_option('afs_plugin_settings', array(
            'api_key' => AFS_API_KEY,
            'cache_time' => 86400 // 24 óra
        ));
    }
}

// Plugin inicializálása
function afs_init() {
    // API osztály példányosítása
    $api = new AFS_API();
    
    // Shortcode osztály példányosítása
    new AFS_Shortcode($api);
    
    // Stílusok és szkriptek betöltése
    add_action('wp_enqueue_scripts', 'afs_enqueue_scripts');
}
add_action('init', 'afs_init');

// Stílusok és szkriptek betöltése
function afs_enqueue_scripts() {
    wp_enqueue_style('afs-styles', AFS_PLUGIN_URL . 'assets/css/style.css', array(), '1.0.0');
    wp_enqueue_script('afs-script', AFS_PLUGIN_URL . 'assets/js/script.js', array('jquery'), '1.0.0', true);
    
    // AJAX URL átadása a JavaScript számára
    wp_localize_script('afs-script', 'afsAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('afs-ajax-nonce')
    ));
}

// Admin menü hozzáadása
function afs_admin_menu() {
    add_menu_page(
        'Auto Felni Szuro',
        'Auto Felni Szuro',
        'manage_options',
        'auto-felni-szuro',
        'afs_admin_page',
        'dashicons-car',
        30
    );
}
add_action('admin_menu', 'afs_admin_menu');

// Admin oldal megjelenítése
function afs_admin_page() {
    include AFS_PLUGIN_DIR . 'admin/admin-page.php';
} 