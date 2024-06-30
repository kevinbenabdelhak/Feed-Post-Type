<?php 

/**
 * Plugin Name: Feed Post Type
 * Plugin URI: https://kevin-benabdelhak.fr/plugins/feed-post-type/
 * Description: Permet de sélectionner les types de contenu à inclure dans les flux RSS.
 * Version: 1.1
 * Author: Kevin BENABDELHAK
 */

if (!defined('ABSPATH')) {
    exit;
}

class FeedPostType {

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('pre_get_posts', array($this, 'filter_post_types_in_feed'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function activate() {
        $default_options = array('post' => 1, 'page' => 1);
        update_option('feed_post_type_settings', $default_options);
    }

    public function add_admin_menu() {
        add_options_page('FeedPostType', 'FeedPostType', 'manage_options', 'feed_post_type', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('pluginPage', 'feed_post_type_settings');

        add_settings_section(
            'feed_post_type_pluginPage_section',
            __('Sélectionnez les types de contenu à inclure dans le flux RSS:', 'feedposttype'),
            array($this, 'settings_section_callback'),
            'pluginPage'
        );

        $post_types = get_post_types(array('public' => true), 'objects');
        foreach ($post_types as $post_type) {
            add_settings_field(
                'feed_post_type_' . $post_type->name,
                $post_type->label,
                array($this, 'settings_field_callback'),
                'pluginPage',
                'feed_post_type_pluginPage_section',
                array('name' => $post_type->name)
            );
        }
    }

    public function settings_section_callback() {
        echo __('Cochez les types de contenu que vous souhaitez inclure dans le flux RSS:', 'feedposttype');
    }

    public function settings_field_callback($args) {
        $options = get_option('feed_post_type_settings', array());
        ?>
        <input type='checkbox' name='feed_post_type_settings[<?php echo $args['name']; ?>]' <?php checked(isset($options[$args['name']]) && $options[$args['name']] == 1); ?> value='1'>
        <?php
 
        $feed_url = get_feed_link() . '?post_type=' . $args['name'];
        ?>
        <small><a href="<?php echo esc_url($feed_url); ?>" target="_blank"><?php echo esc_html($feed_url); ?></a></small>
        <?php
    }

    public function options_page() {
        ?>
        <h1>FeedPostType</h1>
        <form action='options.php' method='post'>
            <?php 
            settings_fields('pluginPage');
            do_settings_sections('pluginPage');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function filter_post_types_in_feed($query) {
        if ($query->is_feed() && $query->is_main_query()) {
            $requested_post_type = isset($_GET['post_type']) ? $_GET['post_type'] : '';

            if ($requested_post_type) {
        
                if ($requested_post_type === 'page') {
                    $query->set('post_type', 'page');
                } else {
                    $feed_options = get_option('feed_post_type_settings', array());
                    if (isset($feed_options[$requested_post_type]) && $feed_options[$requested_post_type]) {
                        $query->set('post_type', $requested_post_type);
                    } else {
                        $query->set('post_type', 'none');
                    }
                }
            } else {
                $feed_options = get_option('feed_post_type_settings', array());
                $valid_post_types = array_keys($feed_options, 1);
                $query->set('post_type', $valid_post_types);
            }
        }
    }

    public function enqueue_styles() {
        wp_enqueue_style('feedposttype-admin-style', plugins_url('style.css', __FILE__));
    }
}

new FeedPostType();
