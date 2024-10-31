<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

// MCEボタンの追加
if( ! class_exists( 'OLM_MCE_Buttons' ) ) {
	class OLM_MCE_Buttons {
		
		// ショートコード名に使用
	    public $related_links_tag = 'related_links'; 
	    public $search_links_tag = 'search_links'; 
	 
		/**
		 * __construct
		 * class constructor will set the needed filter and action hooks
		 *
		 * @param array $args
		 */
		function __construct( $args = array() ) {
			
	        if ( is_admin() ){
				
	            add_action( 'admin_init', array( $this, 'admin_head' ) );
	            add_action( 'admin_enqueue_scripts', array( $this , 'admin_enqueue_scripts' ) );
				add_action( 'admin_footer', array( $this, 'admin_footer' ) );
	        }
			
	    }
	 
	    /**
	     * admin_head
	     * calls your functions into the correct filters
	     * @return void
	     */
	    function admin_head() {
	        // check user permissions
	        if ( ! current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
	            //return;
	        }
	 
	        // check if WYSIWYG is enabled
	        //if ( 'true' == get_user_option( 'rich_editing' ) ) {
	            add_filter( 'mce_external_plugins', array( $this ,'mce_external_plugins' ) );
	            add_filter( 'mce_buttons', array( $this, 'mce_buttons' ) );

	        //}
	    }
	 
	    /**
	     * mce_external_plugins
	     * Adds our tinymce plugin
	     * @param  array $plugin_array
	     * @return array
	     */
	    function mce_external_plugins( $plugin_array ) {
	        $plugin_array[ 'olm' ] = OLM_PATH . 'js/mce-buttons.js';
	        return $plugin_array;
	    }
	 
	    /**
	     * mce_buttons
	     * Adds our tinymce button
	     * @param  array $buttons
	     * @return array
	     */
	    function mce_buttons( $buttons ) {
			
	        array_push( $buttons, $this->search_links_tag );
	        return $buttons;
			
		}
		
		// メディアを追加の横にボタンを設置する場合
	    function editor_buttons( $args = array() ) {

			$args = wp_parse_args( $args, array(
				'target'    => $target,
				'text'      => 'Googlist コード挿入',
				'class'     => 'button',
				'icon'      => 'icon',
				'echo'      => true,
				'shortcode' => false
			) );
			
			$button = '<a 
				href="javascript:void(0);" 
				class="olm_popupbox ' . $args[ 'class' ] . '" 
				title="' . $args[ 'text' ] . '" 
				data-target="' . $args[ 'target' ] . '" 
				data-mfp-src="" 
				data-shortcode="' . ( string ) $args[ 'shortcode' ] . '"
			>' . $args[ 'icon' ] . $args[ 'text' ] . '</a>';

			if( $args[ 'echo' ] == true ) echo $button;
			
			return $button;

	    }
	 
	    /**
	     * admin_enqueue_scripts
	     * Used to enqueue custom styles
	     * @return void
	     */
	    function admin_enqueue_scripts(){
			echo '<style>
				i.mce-i-search_links:before {
				    font-family: "dashicons";
				    content: "\f179";
				}
			</style>';
	    }
		
		function admin_footer() {
		
			echo '
				<div id="search_links_img" data-img="' . get_theme_mod( 'olm_search_links_mce_image', OLM_PATH . 'js/images/search_links.png' ) . '" style="display:none;"></div>
			';
			
		}
	}//end class
}

?>