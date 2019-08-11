<?php

/*
* Plugin Name:     Example Q Gutenberg Plugin
* Description:     This plugin serves as an example of the steps that would need to be taken to incorporate the Gutenberg Editor into the Q Framework
* Version:         0.1
* Author:          Ben Toth
* Author URI:      https://ben-toth.com
* License:         CC
* Class:           Q_Editor_Addon
* Text Domain:     q-editor-addon
*/

defined( 'ABSPATH' ) OR exit;

if ( ! class_exists( 'Q_Editor_Addon' ) ) {

    // instatiate plugin via WP plugins_loaded - init was too late for CPT ##
    add_action( 'plugins_loaded', array ( 'Q_Editor_Addon', 'get_instance' ), 1 );

    class Q_Editor_Addon { 

        private static $instance = null;
        const gb_post_types =  array( // determine which posts types to enable Gutenberg Editor. Post Default is Gutenberg, all other post types, including Custom Post Types default to Classic unless declared here //
            'page',
            #'db_high_school'
            ); 

        public static function get_instance() {

            if ( null == self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;

        }


        /**
         * Instantiate Class
         *
         * @since       0.2
         * @return      void
         */
        private function __construct()
        {


            if ( is_admin() ) {

                //GLOBAL EDITOR SETUP
                //STEP 1 - Determine which post types should use the Gutenberg Block Editor, default is only POSTS
                add_action( 'plugins_loaded', array( $this, 'setup_the_editor' ));
                //STEP 2 - Add Theme Support for Editor Stylesheet
                add_action( 'after_setup_theme', array( $this,'q_editor_styles' ), 10, 10);

                // BLOCK LEVEL SETUP (via ACF)
                if(function_exists('acf_register_block_type')){
                
                //STEP 3 - Add custom Greenheart Block Category (should be done on both admin & front-end)
                add_filter( 'block_categories', array( $this, 'greenheart_block_category'), 10, 2);
                //STEP 4 - Register our Blocks via Advanced Custom Fields (should be done on both admin & front-end)
                
                add_action('acf/init', array( $this, 'acf_register_block_types' ));
    
                } else {  /* ACF Integration error logging here; */ }

                //STEP 5 - Add ACF Field Groups - find in plugin add-fields directory
                load_template( self::get_base('add-fields/example.php') );


            }  else {

                    if(function_exists('acf_register_block_type')){ 
                        add_filter( 'block_categories', array( $this, 'greenheart_block_category'), 10, 2);
                        add_action('acf/init', array( $this, 'acf_register_block_types' ));
                        load_template( self::get_base('add-fields/example.php') );
        
                    } else {  /* ACF Integration error logging here; */ }

                // styles and scripts ##
                add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );

            }

        }

        /**
         * Get Plugin URL
         *
         * @since       0.1
         * @param       string      $path   Path to plugin directory
         * @return      string      Absoulte URL to plugin directory
         */
        public static function get_plugin_url( $path = '' )
        {

            return plugins_url( ltrim( $path, '/' ), __FILE__ );

        }


        public static function get_base ( $path = '') {

            $plugin = str_replace('\\','/', plugin_dir_path( __FILE__ ) ) . ltrim( $path, '/' );
            return $plugin;

        }



        public function setup_the_editor() //FUNCTION TO ENABLE AND CONFIGURE THE EDITOR 
        {
            
            // PART 1: DETERMINE WHICH POST TYPES THE EDITOR WILL RUN ON 
  
            if( self::gb_post_types ){

                add_filter( 'register_post_type_args', array( $this,'filter_the_post_types'), 10, 2 );       

            } else {

                error_log('Custom Post Types to Enable are Not Defined.' );
            
            }

            //PART 2: DETERMINE WHICH BLOCKS WILL BE AVAILABLE BY DEFAULT BY WORDPRESS
            add_filter( 'allowed_block_types', array( $this, 'q_allowed_block_types'), 10, 2 );

        }

        public static function filter_the_post_types($args, $post_type){ //FUNCTION TO DYNAMICALLY ADD WP SUPPORT FOR BLOCK EDITOR TO POST TYPE
            
            if( is_array( self::gb_post_types ) )
            {
    
                foreach ( self::gb_post_types as $cpt ) 
                {
                    if($post_type == $cpt)
                    {
                        $args['show_in_rest']          = true;
                        $args['rest_base']             = $cpt;
                        $args['rest_controller_class'] = 'WP_REST_Posts_Controller';

                        if( isset( $args['supports'] ) && $args['supports'] )
                        {   
                            array_push($args['supports'], 'editor');


                        } else {

                        $args['supports'] = array('editor');

                        } 
                    #error_log('Gutenberg Enabled for post type '.$cpt );
                    return $args;
                    
                    }
                    
                }
            
            } else { /* ERROR LOGGING FOR NO CPTs ENABLED IF NECESSARY */ }
        
        return $args;
        }

        public static function greenheart_block_category( $categories, $post ) {
            return array_merge(
                $categories,
                array(
                    array(
                        'slug' => 'greenheart-blocks',
                        'title' => __( 'Greenheart Blocks', 'greenheart-blocks' ),
                        'icon' => 'heart',
                    ),
                )
            );
        }

         

        public function acf_register_block_types(){

            acf_register_block_type(array(
                'name'   => 'bs_accordion',
                'title'  => __('Accordion Panels'),
                'description'  => __('Expandable content that is hidden underneath an accordion header.'),
                'render_template' => self::get_base('template-parts/q-blocks/bootstrap_accordion.php'), //MUST RETURN FILE PATH NOT URL
                'category'  => 'greenheart-blocks',
                'mode'      => 'preview',
                'icon'      => 'editor-paste-text',
                'enqueue_assets'    => function(){ //FOR PRODUCTION THIS SHOULD OBVIOUSLY BE FIGURED OUT BETTER
                        wp_enqueue_style ('bootstrap-css', self::get_plugin_url( 'template-parts/css/bootstrap.min.css'), array(), '4,2');
                        wp_enqueue_style ('bootstrap-grid-css', self::get_plugin_url( 'template-parts/css/bootstrap-grid.min.css'), array('bootstrap-css'), '4,2');
                        wp_enqueue_style( 'bootstrap_accordion_css', self::get_plugin_url( 'template-parts/css/bootstrap_accordion.css' ), array('bootstrap-css', 'bootstrap-grid-css'), '0.1' );
                        wp_enqueue_script ('bootstrap-js', self::get_plugin_url( 'template-parts/js/bootstrap.min.js'), array('jquery'), '4.2', true );
                        wp_enqueue_script( 'bootstrap-bundle-js', self::get_plugin_url('template-parts/js/bootstrap.bundle.min.js'), array('jquery','bootstrap-js'), '4.2', true);
                        wp_enqueue_script( 'bootstrap_accordion_js', self::get_plugin_url( 'template-parts/js/bootstrap_accordion.js' ), array('jquery', 'bootstrap-js', 'bootstrap-bundle-js'), '0.1', true );
                      },
                'keywords'  => array( 'accordion', 'bootstrap')
            ));

            acf_register_block_type(array(
                'name'   => 'bs_card',
                'title'  => __('Linked Cards'),
                'description'  => __('Image cards with title and text that feature a linked page.'),
                'render_template' => self::get_base('template-parts/q-blocks/bootstrap_link_card.php'), //MUST RETURN FILE PATH NOT URL
                'category'  => 'greenheart-blocks',
                'mode'      => 'preview',
                'icon'      => 'format-image',
                'enqueue_assets'    => function(){ //FOR PRODUCTION THIS SHOULD OBVIOUSLY BE FIGURED OUT BETTER
                        wp_enqueue_style ('bootstrap-css', self::get_plugin_url( 'template-parts/css/bootstrap.min.css'), array(), '4,2');
                        wp_enqueue_style ('bootstrap-grid-css', self::get_plugin_url( 'template-parts/css/bootstrap-grid.min.css'), array('bootstrap-css'), '4,2');
                        wp_enqueue_style( 'bootstrap_link_card_css', self::get_plugin_url( 'template-parts/css/bootstrap_link_card.css' ), array('bootstrap-css', 'bootstrap-grid-css'), '0.1' );

                      },
                'keywords'  => array( 'cards', 'bootstrap')
            ));


        }

        public static function q_editor_styles() {
        // Add support for editor styles.
          add_theme_support( 'editor-styles' );

          add_editor_style( self::get_plugin_url('q-editor.css') );
        }

        public static function q_allowed_block_types( $blocks, $post){

            //FULL BLOCK LIST

            $blocks = array(
                //COMMON BLOCKS
                'core/paragraph',
                'core/image',
                'core/heading',
                'core/subhead',
                'core/gallery',
                'core/list',
                'core/quote',
                'core/audio',
                'core/cover',
                'core/file',
                'core/video',
                //FORMATTING
                'core/table',
                #'core/verse',
                #'core/code',
                'core/freeform', // Classic Editor Block
                'core/html', // Custom HTML Block
                #core/preformatted',
                #'core/pullquote',
                //LAYOUT ELEMENTS
                'core/button',
                #'core/text-columns',  // Columns
                'core/media-text',   // Media and Text
                #'core/more',
                #'core/nextpage'  // Page break
                'core/separator',
                'core/spacer',
                //WIDGETS
                #'core/shortcode',
                #'core/archives',
                #'core/categories',
                #'core/latest-comments',
                #'core/latest-posts',
                #'core/calendar',
                #'core/rss',
                #'core/search',
                #'core/tag-cloud',
                //EMBEDS
                'core/embed',
                'core-embed/twitter',
                'core-embed/youtube',
                'core-embed/facebook',
                'core-embed/instagram',
                'core-embed/wordpress',
                'core-embed/soundcloud',
                'core-embed/spotify',
                'core-embed/flickr',
                'core-embed/vimeo',
                #'core-embed/animoto',
                #'core-embed/cloudup',
                #'core-embed/collegehumor',
                #'core-embed/dailymotion',
                #'core-embed/funnyordie',
                'core-embed/hulu',
                'core-embed/imgur',
                'core-embed/issuu',
                'core-embed/kickstarter',
                'core-embed/meetup-com',
                'core-embed/mixcloud',
                'core-embed/photobucket',
                'core-embed/polldaddy',
                'core-embed/reddit',
                'core-embed/reverbnation',
                'core-embed/screencast',
                'core-embed/scribd',
                'core-embed/slideshare',
                #'core-embed/smugmug',
                #'core-embed/speaker',
                'core-embed/ted',
                'core-embed/tumblr',
                'core-embed/videopress',
                #'core-embed/wordpress-tv',
            );
            //THATS THE KITCHEN SINK, COMMENT OUT ABOVE TO DEACTIVATE A BLOCK GLOBALLY
            if( $post->post_type === 'page' ) {
                array_push($blocks, 'core/shortcode' ); //REACTIVATE BLOCKS BY POST TYPE AS NECESSARY
            }
     
        return $blocks;
        }
    }
}
