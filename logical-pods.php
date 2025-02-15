<?php
/**
 * Plugin Name: Logical Pods
 * Plugin URI: https://github.com/michelediss/logical-pods
 * Description: Manages visibility of specific Pods metaboxes on pages and excecutes custom code.
 * Author: Michele Paolino
 * Author URI: https://michelepaolino.me
 * Version:     1.0.0
 * Text Domain: logical-pods
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class The_Logical_Pods {

    /**
     * Main init hook.
     */
    public static function init() {
        add_action( 'after_setup_theme', [ __CLASS__, 'include_partials' ] );
        add_action( 'init', [ __CLASS__, 'setup_pods_meta_boxes' ] );
    }

    /**
     * Creates /pods/, /pods/partials/, and pods-hide-on-pages.php on plugin activation.
     */
    public static function plugin_activation() {
        $theme_dir = get_stylesheet_directory();
        if ( empty( $theme_dir ) ) {
            $theme_dir = get_template_directory();
        }

        $pods_dir          = $theme_dir . '/pods';
        $pods_partials_dir = $pods_dir  . '/partials';
        $pods_hide_file    = $pods_dir  . '/pods-hide-on-pages.php';

        if ( ! file_exists( $pods_dir ) ) {
            wp_mkdir_p( $pods_dir );
        }
        if ( ! file_exists( $pods_partials_dir ) ) {
            wp_mkdir_p( $pods_partials_dir );
        }
        if ( ! file_exists( $pods_hide_file ) ) {
            $default_config = <<<EOT
<?php
/**
 * Pods metabox config: 'meta_box_id' => array('slug1', 'slug2')
 */
return array(
    'pods-meta-visione'               => array('about'),
    'pods-meta-missione'              => array('about'),
    'pods-meta-contatti'              => array('contact'),
    'pods-meta-informativa-sulla-privacy' => array('privacy-policy')
);
EOT;
            file_put_contents( $pods_hide_file, $default_config );
        }
    }

    /**
     * Includes all .php files in /pods/partials/.
     */
    public static function include_partials() {
        $theme_dir = get_stylesheet_directory();
        if ( empty( $theme_dir ) ) {
            $theme_dir = get_template_directory();
        }

        $pods_partials_dir = $theme_dir . '/pods/partials';
        if ( is_dir( $pods_partials_dir ) ) {
            foreach ( glob( $pods_partials_dir . '/*.php' ) as $partial_file ) {
                include_once $partial_file;
            }
        }
    }

    /**
     * Registers the Pods metabox management hook.
     */
    public static function setup_pods_meta_boxes() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'manage_pods_meta_boxes' ], 100 );
    }

    /**
     * Manages Pods metabox visibility based on page slug.
     */
    public static function manage_pods_meta_boxes() {
        $theme_dir = get_stylesheet_directory();
        if ( empty( $theme_dir ) ) {
            $theme_dir = get_template_directory();
        }

        $pods_hide_file = $theme_dir . '/pods/pods-hide-on-pages.php';
        $config         = [];

        if ( file_exists( $pods_hide_file ) ) {
            $config = include $pods_hide_file;
        }
        if ( ! is_array( $config ) || empty( $config ) ) {
            return;
        }

        global $post;
        if ( ! self::is_page_edit_screen( $post ) ) {
            return;
        }

        $current_slug = $post->post_name;
        foreach ( $config as $meta_box_id => $allowed_slugs ) {
            if ( ! in_array( $current_slug, (array) $allowed_slugs, true ) ) {
                remove_meta_box( $meta_box_id, 'page', 'normal' );
            }
        }
    }

    /**
     * Checks if we're editing a page.
     */
    private static function is_page_edit_screen( $post ) {
        if ( ! isset( $post ) || 'page' !== $post->post_type ) {
            return false;
        }
        return true;
    }
}

register_activation_hook( __FILE__, [ 'The_Logical_Pods', 'plugin_activation' ] );
add_action( 'plugins_loaded', [ 'The_Logical_Pods', 'init' ] );
