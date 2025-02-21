<?php
if (!defined('ABSPATH')) {
    exit;
}

// Beállítások mentése
if (isset($_POST['taf_save_settings']) && check_admin_referer('taf_settings_nonce')) {
    $settings = array(
        'api_key' => sanitize_text_field($_POST['api_key']),
        'cache_time' => absint($_POST['cache_time'])
    );
    
    update_option('taf_plugin_settings', $settings);
    echo '<div class="notice notice-success"><p>A beállítások sikeresen mentve!</p></div>';
}

// Aktuális beállítások lekérése
$settings = get_option('taf_plugin_settings', array(
    'api_key' => TAF_API_KEY,
    'cache_time' => 86400
));
?>

<div class="wrap">
    <h1>TopAlufelni Filter Beállítások</h1>
    
    <div class="card">
        <h2>API Beállítások</h2>
        <form method="post" action="">
            <?php wp_nonce_field('taf_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="api_key">API Kulcs</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="api_key" 
                               name="api_key" 
                               value="<?php echo esc_attr($settings['api_key']); ?>" 
                               class="regular-text">
                        <p class="description">
                            A Wheel Size API kulcs. Regisztrálj a <a href="https://developer.wheel-size.com/" target="_blank">developer.wheel-size.com</a> oldalon egy kulcsért.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="cache_time">Cache Időtartam</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="cache_time" 
                               name="cache_time" 
                               value="<?php echo esc_attr($settings['cache_time']); ?>" 
                               class="regular-text">
                        <p class="description">
                            Az API válaszok cache-elési ideje másodpercekben. Alapértelmezett: 86400 (24 óra)
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" 
                       name="taf_save_settings" 
                       class="button button-primary" 
                       value="Beállítások Mentése">
            </p>
        </form>
    </div>

    <div class="card">
        <h2>Használati Útmutató</h2>
        <p>A felni szűrő megjelenítéséhez használd a következő shortcode-ot:</p>
        <code>[topalufelni_filter]</code>
        
        <h3>Shortcode Használata</h3>
        <ol>
            <li>Másold ki a fenti shortcode-ot</li>
            <li>Illeszd be egy oldalba vagy bejegyzésbe</li>
            <li>A szűrő automatikusan megjelenik a tartalomban</li>
        </ol>
        
        <h3>Funkciók</h3>
        <ul>
            <li>Autó gyártók, modellek és évjáratok szűrése</li>
            <li>Kompatibilis felnik listázása</li>
            <li>Automatikus cache-elés a gyorsabb betöltésért</li>
            <li>Reszponzív dizájn</li>
        </ul>
    </div>

    <div class="card">
        <h2>Támogatás</h2>
        <p>Ha kérdésed vagy problémád van a plugin használatával kapcsolatban, keress bátran:</p>
        <p><strong>Email:</strong> pusztaizsomborr@gmail.com</p>
    </div>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-top: 20px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.card h2 {
    margin-top: 0;
    color: #1d2327;
    font-size: 1.3em;
    margin-bottom: 1em;
}

.card h3 {
    font-size: 1.1em;
    margin-top: 1.5em;
}

code {
    background: #f0f0f1;
    padding: 3px 5px;
    border-radius: 3px;
}

.form-table th {
    width: 200px;
}
</style> 