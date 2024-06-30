<?php
/**
 * Plugin Name: Feed Post Type
 * Plugin URI: https://kevin-benabdelhak.fr/plugins/feed-post-type/
 * Description: Permet de sélectionner les types de contenu à inclure dans les flux RSS.
 * Version: 1.0
 * Author: Kevin BENABDELHAK
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}

class FeedPostType {
    
    public function __construct() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'settings_init' ) );
        add_filter( 'request', array( $this, 'add_custom_post_type_to_feed' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    }

    public function activate() {
        $default_options = array(
            'post' => 1, // Init to 1 to check by default
            // Initialize other default post types if necessary
        );
        
        $existing_options = get_option( 'feed_post_type_settings' );
        
        if ( false === $existing_options ) {
            update_option( 'feed_post_type_settings', $default_options );
        }
    }

    public function add_admin_menu() {
        add_options_page( 'FeedPostType', 'FeedPostType', 'manage_options', 'feed_post_type', array( $this, 'options_page' ) );
    }

    public function settings_init() {
        register_setting( 'pluginPage', 'feed_post_type_settings' );

        add_settings_section(
            'feed_post_type_pluginPage_section', 
            __( 'Sélectionnez les types de contenu à inclure dans le flux RSS:', 'feedposttype' ), 
            array( $this, 'settings_section_callback' ), 
            'pluginPage'
        );

        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        foreach ( $post_types as $post_type ) {
            add_settings_field( 
                'feed_post_type_' . $post_type->name,
                $post_type->label,
                array( $this, 'settings_field_callback' ),
                'pluginPage',
                'feed_post_type_pluginPage_section',
                array( 'name' => $post_type->name )
            );
        }
    }

    public function settings_section_callback() {
        echo __( 'Cochez les types de contenu que vous souhaitez inclure dans le flux RSS:', 'feedposttype' );
    }

    public function settings_field_callback( $args ) {
        $options = get_option( 'feed_post_type_settings', array() );
        ?>
        <input type="checkbox" name="feed_post_type_settings[<?php echo $args['name']; ?>]" <?php checked( isset( $options[ $args['name'] ] ) && $options[ $args['name'] ] == 1 ); ?> value="1">
        <?php
    }

    public function options_page() {
        ?>
        <h1>FeedPostType</h1>

        <form action='options.php' method='post'>
            <?php
            settings_fields( 'pluginPage' );
            do_settings_sections( 'pluginPage' );
            submit_button();
            ?>
        </form>
        <?php
    }

    public function add_custom_post_type_to_feed( $qv ) {
        if ( isset( $qv['feed'] ) ) {
            $options = get_option( 'feed_post_type_settings' );

            if ( ! empty( $options ) ) {
                $post_types = array_keys( array_filter( $options ) );
                if ( ! empty( $post_types ) ) {
                    $qv['post_type'] = $post_types;
                }
            } else {
                // Define empty post types to avoid default 'post' inclusion
                $qv['post_type'] = array('none');
            }
        }
        return $qv;
    }

    public function enqueue_styles() {
        wp_enqueue_style( 'feedposttype-admin-style', plugins_url( 'style.css', __FILE__ ) );
    }
}

new FeedPostType();

?>
