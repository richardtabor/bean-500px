<?php 
/*--------------------------------------------------------------------

    Widget Name: Bean 500px Widget
    Widget URI: http://www.themebeans.com/?ref=plugin_bean_500px
    Description:  A widget that displays your most recent 500px photos, your 500px feed (photos of friends your following) or your favorited posts on 500px
    Author: Kamil Waheed / ThemeBeans
    Author URI: http://www.themebeans.com/?ref=plugin_bean_500px
    Version: 1.0

/*--------------------------------------------------------------------*/


// WIDGET CLASS
class widget_bean_500px extends WP_Widget {

    private $API_CONSUMER_KEY = "XDtqmkDVnElsZvENizrfyWmJ8W2FSVJBEkJrPjiD";
    private $API_URI_BASE = "https://api.500px.com/v1/";
    private $API_PHOTOS_ENDPOINT = "photos";

    private $db_option_key = "bean500px_settings";

    public function __construct() {
        parent::__construct(
            'bean_500px', // BASE ID
            'Bean 500px', // NAME
            array( 'description' => __( 'Add a 500px feed widget.', 'bean' ), )
        );

        if ( is_active_widget(false, false, $this->id_base) )
            add_action( 'wp_head', array(&$this, 'load_widget_style') );
    }


    /** 
     * Widget style is loaded
     * hooked to wp_head
     *
     * @return void
     */
    public function load_widget_style() {
        $default_css_url = plugin_dir_url(__FILE__) . '500px.css';
        wp_enqueue_style( 'bean-500px-style', $default_css_url, false, '1.0', 'all' );
    }

    /**
     * Render the front-end widget output
     * 
     * @return void
     */
    public function widget($args, $instance) {
        extract($args);
        if(!empty($instance['title'])){ $title = apply_filters( 'widget_title', $instance['title'] ); }

        echo $before_widget;
        if ( ! empty( $title ) ){ echo $before_title . $title . $after_title; }

        $desc = $instance['desc'];
        if($desc != '') : ?><p><?php echo $desc; ?></p><?php endif;

        $photos = $this->get500px_photos( $instance['username'], $instance['show'], $instance['show_count'], $instance['cachetime'] );

        if ( !$photos ) {
            $error = new stdClass();
            $error->status = "001";
            $error->error = "Failed to retrieve response from 500px. Check if the host has access to internet.";

            $this->render_error( $error );
        }
        else if ( isset( $photos->error ) ) {
            $this->render_error($photos);
        } else {
            $photos = $photos->photos;

            echo "<div class='bean500px-image-wrapper'>";  

            if (count($photos) > 0) {          
                foreach($photos as $photo) {
                    $this->render_photo( $photo );
                }
            } else {
                echo "<p>";
                echo "500px returned 0 photos for the current settings.";
                echo "</p>";
            }

            echo "</div>";            
        }

        echo $after_widget;
    }


    /**
     * Based a $photo object, render a front-end image markup
     *
     * @param object $photo An object as returned from the 500px API
     *
     * @return void
     */
    private function render_photo($photo) {
        $image = $photo->images;
        $image = $image[0];

        $image_src = $image->url;
        $image_name = $photo->name;
        $image_link = "http://500px.com" . $photo->url;

        echo "<div class='bean500px_badge_image fadein'>";
        echo "<a href='$image_link' target='_blank'>";
        echo "<img src='$image_src' alt='$image_name'>";
        echo "</a>";
        echo "</div>";
    }

    /**
     * Render the error markup given the error object $error
     *
     * @param object $error An error object as returned from the 500px API
     *
     * @return void
     */
    private function render_error($error) {
        echo "<p>";
        echo "<strong>";
        echo __("500px API error!", "bean");
        echo "</strong>";
        echo  "</p>";

        echo "<p>";
        echo $error->error;
        echo "(" . $error->status . ")";
        echo "</p>";
    }


    /**
     * The callback function to returned the sanitized values of the widget settings
     *
     * @param array $new_instance An array with key/value pairs of new settings
     * @param array $old_instance An array with key/value pairs of old (current) settings
     *
     * @return array Sanitized array with key/value pairs of settings to save
     */
    public function update($new_instance, $old_instance) {
        $instance = array();

        $settings = $this->get_widget_settings();

        foreach($settings as $key => $value) {
            if ( ! isset( $new_instance[$key] ) ) continue;

            switch($value['type']) {
                case 'textarea':
                    $instance[$key] = stripslashes( $new_instance[$key] );

                    break;

                case 'number':
                    $instance[$key] = strip_tags( $new_instance[$key] );
                    $args = isset( $value['args'] ) ? $value['args'] : array();
                    $default = $value['default'];
                    
                    if ( is_numeric( $new_instance[$key] ) ) {
                        if ( isset( $args['max'] ) && $new_instance[$key] > $args['max'] ) {
                            $instance[$key] = $args['max'];
                        } elseif ( isset( $args['min'] ) && $new_instance[$key] < $args['min'] ) {
                            $instance[$key] = $args['min'];
                        }
                    } else {
                        $instance[$key] = $default;
                    }

                    break;

                default:
                    $instance[$key] = strip_tags( $new_instance[$key] );

                    break;
            }
        }

        $this->force_invalidate_raw_response_cache();

        return $instance;
    }

    /**
     * Returns a pre-defined array of all the settings to be saved and rendered by the widget
     *
     * @return array The settings array
     */
    private function get_widget_settings() {
        return array(
            'title'        => array(
                                'title'     => __("Title", "bean"),
                                'default'   => __("Bean 500px Plugin", "bean"),
                                'type'      => "text",
                                'display'   => "full-text"
                           ),
            'desc'         => array(
                                'title'     => "",
                                'default'   => "",
                                'type'      => "textarea",
                                'args'      => array(
                                                    'top'   => '-8px',
                                                    'rows'  => "5",
                                                    'cols'  => "15"
                                               )
                           ),
            'username'     => array(
                                'title'     => __("Username", "bean"),
                                'default'   => "feliciasimion",
                                'type'      => "text",
                                'display'   => "full-text"
                           ),
            'show'         => array(
                                'title'     => __("Show", "bean"),
                                'default'   => "recent",
                                'type'      => "select",
                                'args'      => array(
                                                    'options'   => array(
                                                                        'user'              => __("Recently Uploaded", "bean"),
                                                                        'user_friends'      => __("Following", "bean"),
                                                                        'user_favorites'    => __("Favorites", "bean")
                                                                   )
                                               )
                           ),
            'show_count'   => array(
                                'title'     => __("Show Count", "bean"),
                                'default'   => "6",
                                'type'      => "number",
                                'display'   => "full-text",
                                'args'      => array(
                                                    'max'       => 100,
                                                    'min'       => 0
                                               )
                           ),
            'cachetime'    => array(
                                'title'     => __("Cache Time", "bean"),
                                'default'   => "5",
                                'type'      => "number",
                                'display'   => "small-text",
                                'args'      => array(
                                                    'suffix'    => 'hours',
                                                    'max'       => INF,
                                                    'min'       => 0
                                               )
                           ),
        );
    }


    /**
     * Render the widget settings panel (under WP Admin -> Appearance -> Widgets)
     *
     * @return void
     */
    public function form($instance) {
        $settings = $this->get_widget_settings();

        // construct the defaults array
        $defaults = array();
        foreach($settings as $key => $value) {
            $defaults[$key] = $value['default'];
        }

        $instance = wp_parse_args( (array) $instance, $defaults );


        foreach($settings as $key => $value) {
            $this->generateFormField( $value['title'], 
                                      $key,
                                      isset( $value['display'] ) ? $value['display'] : $value['type'], 
                                      $instance[ $key ], 
                                      isset( $value['suffix'] ) ? $value['suffix'] : '',
                                      isset( $value['args'] ) ? $value['args'] : ''
                                    );
        }

    }

    /**
     * Render a form field based on the arguments
     *
     * @param string $title The text of the <label> for the field
     * @param string $name The "name" attribute value to use in the markup
     * @param string $display The display of the form field to render. Supported values are: textarea, select, small-text, and full-text
     * @param string $value The value to populate in the form field
     * @param string $suffix Some extra markup(or plain text) to render right after the field
     * @param array $args Extra arguments depending on the $type of field
     *
     * @return void
     */
    private function generateFormField( $title, $name, $display, $value = "", $suffix = "", $args = array() ) {
        $top = isset( $args['top'] ) ? $args['top'] : NULL;
        $style = $top ? "style='margin-top: $top;'" : "";

        $id = $this->get_field_id($name);
        $name = $this->get_field_name($name);

        echo "<p $style>";

        if ( !empty($title) ) {
            echo "<label for='$id'>$title&nbsp;</label>";
        }

        switch( $display ) {
            case 'textarea':
                $rows = isset($args['rows']) ? $args['rows'] : '';
                $cols = isset($args['cols']) ? $args['cols'] : '';

                echo "<textarea name='$name' id='$id' class='widefat' rows='$rows' cols='$cols'>$value</textarea>";
                break;

            case 'select':
                echo "<select name='$name' class='widefat' id='$id'>";

                $options = isset( $args['options'] ) ? $args['options'] : array();

                foreach($options as $key => $name) {
                    $selected = $key == $value ? "selected='selected'" : '';
                    echo "<option value='$key' $selected >$name</option>";
                }

                echo "</select>";
                break;

            case 'small-text':
                echo "<input type='text' name='$name' id='$id' value='$value' class='small-text'>";
                break;

            default:
                echo "<input type='text' name='$name' id='$id' value='$value' class='widefat'>";
                break;
        }

        if ( isset($args['suffix']) ) {
            echo ' ' . $args['suffix'];
        }

        echo "</p>";
    }


    /*--------------------------------------------------------------------*/
    /*  500px API COMMUNICATION
    /*--------------------------------------------------------------------*/


    /**
     * Return the cached or new JSON object from the 500px API based on the arguments
     *
     * @param string $username The 'username' value to pass to 500px API
     * @param string $feature The 'feature' value to pass to 500px API
     * @param string $show_count The number of results to return
     * @param int $cache_duration (OPTIONAL) Number of seconds the cache lasts
     *
     * @return object|false A JSON object as received from the 500px API; false otherwise
     *
     */
    private function get500px_photos( $username, $feature, $show_count, $cache_duration = 0 ) {
        $cache = $this->get_raw_response_cache($cache_duration);

        if ($cache) {
            $raw_response = $cache;
        } else {
            $raw_response = $this->get500px_raw_response( $username, $feature, $show_count );

            $this->set_raw_response_cache( $raw_response );
        }

        if (!empty($raw_response)) {
            $response = json_decode( $raw_response );
            return $response;
        } else {
            return false;
        }
    }

    /**
     * Query the 500px API and return the raw response based on the arguments
     *
     * @param string $username The 'username' value to pass to 500px API
     * @param string $feature The 'feature' value to pass to 500px API
     * @param string $show_count The number of results to return
     *
     * @return string The raw response string from the 500px API
     */

    private function get500px_raw_response( $username, $feature, $show_count ) {
        $endpoint_URI = $this->API_URI_BASE . $this->API_PHOTOS_ENDPOINT;
        $url_vars = array(
                        "consumer_key"  => $this->API_CONSUMER_KEY,
                        "username"      => $username,
                        "feature"       => $feature,
                        "image_size"    => 2,
                        "rpp"           => is_numeric( $show_count ) ? $show_count : 6
                    );

        $endpoint_URI .= '?' . http_build_query( $url_vars );

        $curl = curl_init();
        $curl_options = array(
            CURLOPT_URL             => $endpoint_URI,
            CURLOPT_HEADER          => false,
            CURLOPT_RETURNTRANSFER  => true
        );

        curl_setopt_array( $curl, $curl_options );
        return curl_exec( $curl );
    }


    /*--------------------------------------------------------------------*/
    /*  OPTIONS API INTERFACE
    /*--------------------------------------------------------------------*/

    /**
     * Return the 500px raw response cache if saved and valid
     *
     * @return string|false Cache if valid; false otherwise
     */
    private function get_raw_response_cache( $cache_duration = 0 ) {
        $plugin_settings = get_option( $this->db_option_key );

        if ( $plugin_settings ) {
            if ( isset( $plugin_settings['photos_cache'] ) &&
                 isset( $plugin_settings['cache_time'] ) &&
                 $plugin_settings['cache_time'] != 0 ) {

                $current_time = time();
                $cache_time = $plugin_settings['cache_time'];
                $difference = $current_time - $cache_time;
                $cache_duration = intval( $cache_duration ) * 3600;

                if ($difference < $cache_duration) {
                    return $plugin_settings['photos_cache'];
                }
            }
        }

        return false;
    }

    /**
     * Save the 500px raw response in cache
     * 
     * @param string $cache The raw cache string to save
     *
     * @return void
     */
    private function set_raw_response_cache($cache) {
        $plugin_settings = array();
        $saved_plugin_settings = get_option( $this->db_option_key );

        if ($saved_plugin_settings) {
            $plugin_settings = $saved_plugin_settings;
        }

        $plugin_settings['photos_cache'] = $cache;
        $plugin_settings['cache_time'] = time();

        update_option( $this->db_option_key, $plugin_settings );
    }

    /**
     * Invalidate the 500px raw response cache that maybe saved
     *
     * @return void
     */
    private function force_invalidate_raw_response_cache() {
        $plugin_settings = array();
        $saved_plugin_settings = get_option( $this->db_option_key );

        if ($saved_plugin_settings) {
            $plugin_settings = $saved_plugin_settings;
        }

        $plugin_settings['cache_time'] = 0;

        update_option( $this->db_option_key, $plugin_settings );
    }
}


// REGISTER WIDGET
function register_bean500px_widget(){
    register_widget('widget_bean_500px');
}
add_action('init', 'register_bean500px_widget', 1);

?>