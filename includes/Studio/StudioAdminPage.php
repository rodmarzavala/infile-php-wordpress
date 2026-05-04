<?php

namespace InfilePhp\WordPress\Studio;

class StudioAdminPage
{
    public static function register()
    {
        $page = new self();
        add_action('admin_menu', array($page, 'add_menu_page'));
    }

    public function add_menu_page()
    {
        add_submenu_page(
            'woocommerce',
            'FEL Studio',
            'FEL Studio',
            'manage_options',
            'infile-fel-studio',
            array($this, 'render_page')
        );
    }

    public function render_page()
    {
        $index_path = INFILE_PHP_PLUGIN_DIR . 'public/studio-ui/index.html';

        if (!file_exists($index_path)) {
            echo '<div class="wrap"><p>FEL Studio assets not found. Please build the studio-ui package.</p></div>';
            return;
        }

        $html = file_get_contents($index_path);

        // Replace the Vite base placeholder with the real plugin URL
        $studio_url = INFILE_PHP_PLUGIN_URL . 'public/studio-ui';
        $html = str_replace('/__FEL_STUDIO_BASE__', $studio_url, $html);

        // Inject WP REST API config + nonce so React can call the API authenticated
        $config = '<script>';
        $config .= 'window.InfileStudioData = {';
        $config .= 'apiUrl:' . json_encode(rest_url('infile-php/v1/studio')) . ',';
        $config .= 'nonce:' . json_encode(wp_create_nonce('wp_rest'));
        $config .= '};';
        $config .= '</script>';

        // Inject just before </head>
        $html = str_replace('</head>', $config . '</head>', $html);

        // Strip the outer <html>/<head>/<body> wrapper so it embeds in WP admin cleanly.
        // Extract only the <head> links/scripts and the <body> content.
        preg_match('/<head>(.*?)<\/head>/s', $html, $head_match);
        preg_match('/<body>(.*?)<\/body>/s', $html, $body_match);

        $head_content = $head_match[1] ?? '';
        $body_content = $body_match[1] ?? '';

        // Remove <meta> and <title> from head — WP handles those
        $head_content = preg_replace('/<meta[^>]+>/', '', $head_content);
        $head_content = preg_replace('/<title>[^<]*<\/title>/', '', $head_content);

        // Output WP admin wrapper + extracted assets + body
        echo '<div class="fel-studio-page">';
        echo $head_content;  // <link> CSS + <script> JS from Vite
        echo $body_content;  // <div id="root"></div>
        echo '</div>';
    }
}
