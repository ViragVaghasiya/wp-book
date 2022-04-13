<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @since 1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       WP Book
 * Plugin URI:        https://github.com/rtlearn/wpcs-ViragVaghasiya
 * Description:       A Book Management Plugin.
 * Version:           1.0.0
 * Author:            Virag Vaghasiya
 * Author URI:        https://github.com/ViragVaghasiya
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpbk
 */

if ( ! defined( 'WPINC' ) ) {
    die( 'No direct access.' );
}

/**
 * Paths and Required Constants
 */
define( 'WPBK_VERSION'      , '1.0.0' );
define( 'WPBK_PLUGIN_DIR'   , plugin_dir_path( __FILE__ ) );
define( 'WPBK_PLUGIN_URL'   , plugin_dir_url( __FILE__ ) );
define( 'WPBK_PLUGIN_FILE'  , __FILE__ );
define( 'WPBK_TEMPLATE_DIR' , WPBK_PLUGIN_DIR . '/includes/templates' );
define( 'WPBK_VALIDATOR_DIR', WPBK_PLUGIN_DIR . '/includes/validators' );
define( 'WPBK_INCLUDES_DIR' , WPBK_PLUGIN_DIR . '/includes' );
define( 'WPBK_ASSETS_URL'   , WPBK_PLUGIN_URL . 'assets' );

// Book Meta Data Display Options Array Constant
define( 'WPBK_BOOKINFO_DISPLAY_OPTIONS' , array(
    'author_name'    => __( 'Author Name', 'wpbk' ),
    'published_year' => __( 'Publish Year', 'wpbk' ),
    'price'          => __( 'Price', 'wpbk' ),
    'publisher'      => __( 'Publisher', 'wpbk' ),
    'edition'        => __( 'Edition', 'wpbk' ),
    'url'            => __( 'Book URL', 'wpbk' ),
    'book_pages'     => __( 'No. of Pages', 'wpbk' ),
    'description'    => __( 'Description', 'wpbk' ),
    'rating'         => __( 'Rating', 'wpbk' ),
    'language'       => __( 'Language', 'wpbk' ),
    'category'       => __( 'Category', 'wpbk' ),
    'tag'            => __( 'Tag', 'wpbk' ),
));

// Currency Options Array Constant
define( 'WPBK_CURRENCY_OPTIONS', array(
    /* translators: %s: Currency Unit */ 
    'INR' => sprintf( __( 'Rupees %s', 'wpbk' ), '(INR)' ),
    /* translators: %s: Currency Unit */
    'USD' => sprintf( __( 'US Dollar %s', 'wpbk' ), '(USD)' ),
    /* translators: %s: Currency Unit */
    'EUR' => sprintf( __( 'Euro %s', 'wpbk' ), '(EUR)' )
));

// Currency Forex Rate Type Array Constant
define( 'WPBK_FX_RATE_TYPES', array( 
    'user_defined' => __( 'User Defined Forex Rate', 'wpbk' ), 
    'api_managed'  => __( 'API Managed Forex Rate', 'wpbk' ), 
));

 
/**
 * Includes Required Files
 */
 require_once WPBK_INCLUDES_DIR  . '/class-wpbk-book-admin-settings.php';
 require_once WPBK_INCLUDES_DIR  . '/class-wpbk-db-io.php';
 require_once WPBK_INCLUDES_DIR  . '/class-wpbk-post-type-taxonomy.php';
 require_once WPBK_VALIDATOR_DIR . '/class-wpbk-settings-form-validator.php';
 require_once WPBK_INCLUDES_DIR  . '/class-wpbk-book-metadata.php';
 require_once WPBK_INCLUDES_DIR  . '/class-wpbk-currency-price-manipulation.php';
 require_once WPBK_INCLUDES_DIR  . '/class-wpbk-update-currency-forex-rate.php';
 require_once WPBK_INCLUDES_DIR  . '/class-wpbk-book-information-api.php';
 require_once WPBK_INCLUDES_DIR  . '/class-wpbk-shortcode-widgets.php';
 require_once WPBK_INCLUDES_DIR  . '/class-wpbk-category-books-widget.php';
 require_once WPBK_INCLUDES_DIR  . '/class-wpbk-top-category-widget.php';
 require_once WPBK_INCLUDES_DIR  . '/class-wpbk-deactivate-plugin.php';
 require_once WPBK_INCLUDES_DIR  . '/class-wpbk-clean-uninstall.php';
 
/**
 * Plugin Initializer Class
 */
class WPBk_WP_Book {

    /**
     * Instance Variable
     *
     * @var static
     */
    private static $wpbk_instance = null;

    /**
     * Creates Instance
     *
     * @return object
     */
    public static function wpbk_init() {
        if ( is_null( self::$wpbk_instance ) ) {
            self::$wpbk_instance = new WPBk_WP_Book();
        }
    }

    private function __construct() {
        
        flush_rewrite_rules();

        // registers taxonomies and post type
        if ( class_exists( 'WPBk_Post_Type_Taxonomy' ) ) {
            $post_taxonomy = new WPBk_Post_Type_Taxonomy();
            $post_taxonomy->wpbk_rgstr_book_post_type();
            $post_taxonomy->wpbk_rgstr_book_ctgr_taxonomy();
            $post_taxonomy->wpbk_rgstr_book_tag_taxonomy();
            unset($post_taxonomy);
        }

        // registers wp book admin settings
        if ( class_exists( 'WPBk_Book_Admin_Settings' ) ) {
            $wpbk_settings = new WPBk_Book_Admin_Settings();
            $wpbk_settings->wpbk_add_settings_action();
            unset($wpbk_settings);
        }

        // updates api currency forex rates
        if ( class_exists( 'WPBk_Update_Currency_Forex_Rate' ) ) {
            $forex_rate_update = new WPBk_Update_Currency_Forex_Rate();
            $forex_rate_update->wpbk_forex_rate_update_init();
            unset($forex_rate_update);
        }

        // activation hook for schema installation
        if ( class_exists( 'WPBk_DB_IO' ) ) {
            register_activation_hook( __FILE__, array( 'WPBk_WP_Book', 'wpbk_activate_plugin' ) );
            global $wpdb;
            $wpdb->wpbk_bookmeta = $wpdb->prefix . 'wpbk_bookmeta';
        }

        // price manipulation
        if ( class_exists( 'WPBk_Currency_Price_Manipulation' ) ) {
            $price_manipulation = new WPBk_Currency_Price_Manipulation();
            add_action('wp_ajax_wpbk_base_currency_book_price_update', 
                array( $price_manipulation, 'wpbk_base_currency_book_price_update' ));
            unset($price_manipulation);
        }

        // book information api
        if ( class_exists( 'WPBk_Book_Information_API' ) ) {
            $book_api = new WPBk_Book_Information_API();
            $book_api->wpbk_book_information_api_init();
            unset($book_api);
        }

        // registers book metaboxes and admin settings
        if ( class_exists( 'WPBk_Book_Metadata' ) ) {
            $wpbk_meta = new WPBk_Book_Metadata();
            $wpbk_meta->wpbk_register_book_meta_box();
            $wpbk_meta->wpbk_save_book_information_action();
            $wpbk_meta->wpbk_delete_book_post_action();
            $wpbk_meta->wpbk_book_add_custom_query();
            unset($wpbk_meta);
        }

        // book shortcode
        if ( class_exists( 'WPBk_Shortcode_Widgets' ) ) {
            $shortcds_wdgts = new WPBk_Shortcode_Widgets();
            $shortcds_wdgts->wpbk_add_book_shortcode();
            unset($shortcds_wdgts);
        }

        // category book widget
        if ( class_exists( 'WPBk_Category_Books_Widget' ) ) {
            $cat_wdgt = new WPBk_Category_Books_Widget();
            $cat_wdgt->wpbk_load_category_widget();
            unset($cat_wdgt);
        }

        // top 5 category widget
        if ( class_exists( 'WPBk_Top_Category_Widget' ) ) {
            $top_cat_wdgt = new WPBk_Top_Category_Widget();
            $top_cat_wdgt->wpbk_add_top_category_dashboard_widget();
            unset($top_cat_wdgt);
        }
        
        // deactivation hook
        if ( class_exists( 'WPBk_Plugin_Deactivate' ) ) {
            register_deactivation_hook( __FILE__, array( 'WPBk_Plugin_Deactivate', 'wpbk_remove_plugin_related_stuff' ) );
        }

        // uninstallation hook
        if ( class_exists( 'WPBk_Clean_Uninstall' ) ) {
            register_uninstall_hook( __FILE__, array( 'WPBk_Clean_Uninstall', 'wpbk_book_plugin_uninstall' ) );
        }
    }

    /**
     * Installs Required Schema
     *
     * @return void
     */
    public static function wpbk_activate_plugin() {
        
       if ( class_exists( 'WPBk_DB_IO' ) ) {
           $wpbk_bookmeta_db = new WPBk_DB_IO();
           $wpbk_bookmeta_db->wpbk_install();
           unset($wpbk_bookmeta_db);
       }
    }
}

// Initializes the plugin
WPBk_WP_Book::wpbk_init();

