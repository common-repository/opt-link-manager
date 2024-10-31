<?php
/*
 * Opt Link Manager
 *
 * @package     Opt Link Manager
 * @author      Nora
 * @copyright   2016 Nora https://wp-works.net
 * @license     GPL-2.0+
 * 
 * @wordpress-plugin
 * Plugin Name: Opt Link Manager
 * Plugin URI: https://wp-works.net
 * Description: Enable your wp-site to get links from your contents pages, import/export link data by csv, append links card to content pages by post setting( Metabox ), Change target contents texts which matches keywords as meta data of links into link-texts by wordpress filter.
 * Version: 1.0.3
 * Author: Nora
 * Author URI: https://wp-works.net
 * Text Domain: opt-link-manager
 * Domain Path: /languages/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

// 固定値
if( ! defined( 'OLM_NAME' ) ) { define( 'OLM_NAME', 'Opt Link Manager' ); }
if( ! defined( 'OLM_PREFIX' ) ) { define( 'OLM_PREFIX', 'olm-' ); }
if( ! defined( 'OLM_OPTION' ) ) { define( 'OLM_OPTION', 'olm_' ); }
if( ! defined( 'OLM_MODS' ) ) { define( 'OLM_MODS', 'olm_mods_' ); }
if( ! defined( 'OLM_POST_META' ) ) { define( 'OLM_POST_META', '_olm_post_meta_' ); }

// プラグインのディレクトリ
if( ! defined( 'OLM_PATH' ) ) { define( 'OLM_PATH', plugin_dir_url( __FILE__ ) ); }

// サイトの名前
if( ! defined( 'SITE_NAME' ) ) define( 'SITE_NAME', get_bloginfo( 'name' ) );
// サイトの詳細
if( ! defined( 'SITE_DESCRIPTION' ) ) define( 'SITE_DESCRIPTION', get_bloginfo( 'description' ) );
// サイトのホームURL
if( ! defined( 'SITE_URL' ) ) define( 'SITE_URL', esc_url( home_url() ) );


if( ! class_exists( 'Opt_Link_Magager' ) ) {
	class Opt_Link_Magager {

		function __construct() {
			$this->init();
			$this->add_actions();
			$this->add_filters();
		}

		#
		# 初期化
		#
		function init() {

			// ショートコードの追加
			add_shortcode( 'related_links', array( $this, 'related_links_func' ) );
			add_shortcode( 'search_links', array( $this, 'olm_keyword_search_shortcode' ) );

			if( is_admin() ) {

				// オプションページ
				require_once( 'admin/class/original/class-olm-options-page.php' );
				new OLM_Options_Page;

				// メタボックス
				require_once( 'admin/class/original/class-olm-meta-boxes.php' );
				new OLM_Meta_Boxes;

				// 編集ボタン
				require_once( 'admin/class/original/class-olm-mce-buttons.php' );
				new OLM_MCE_Buttons;

			} else {

			}

		}
		#
		# ショートコードの登録
		#
			// 関連リンク用ショートコード
			function related_links_func( $atts ) { 
				$atts = shortcode_atts(
					array(
						'cat' => '',
						'num' => 3,
						'order'   => 'DESC',
						'orderby' => 'none',
					),
					$atts, 
					'related_links'
				);
					
				$links_category = rawurlencode( $atts[ 'cat' ] );
				$links_num = $atts[ 'num' ];
				$links_order = $atts[ 'order' ];
				$links_orderby = $atts[ 'orderby' ];
				
				$args = array(
					'links_category' => $links_category,
					'links_num'      => $links_num,
					'field'          => 'slug',
					'order'          => $links_order,
					'links_orderby'  => $links_orderby,
				);
				
				return $this->olm_get_related_links( $args );
			}

			// リンクサーチ用ショートコード
			function olm_keyword_search_shortcode( $atts ) {
				
				$atts = shortcode_atts(
					array(
						'key' => '',
						'num' => 1,
						'order'   => 'DESC',
						'orderby' => 'none'
					),
					$atts, 
					'search_links'
				);
				
				$links_key = $atts[ 'key' ];
				$links_num = $atts[ 'num' ];
				$links_order = $atts[ 'order' ];
				$links_orderby = $atts[ 'orderby' ];
				
				if( $links_num == 0 ) { return; }
				
				$args = array(
					'order' => $links_order,
					'orderby' => $links_orderby,
					'post_type' => 'olm-link',
					'posts_per_page' => -1
				);
				
				$link_collection = get_posts( $args );

				//print_r( $link_collection ); // チェック用
				
				
				$return = '<div class="link-set"><ul class="link-set-ul">';
				
				$count = 0;
				
				foreach ( $link_collection as $link ) {
					
					if( $count >= $links_num ) { break; }
					
					$search_title = get_the_title( $link->ID );
					$search_permalink = get_permalink( $link->ID );
					
					/* チェック用 
					echo $search_title;
					echo $search_permalink;
					*/
								
					$content = $link->post_content;
					$content = strip_tags( $content );
					
					//echo $content; // チェック用
				
					$search1 = strpos( $content, $links_key );
					$search2 = strpos( $search_title, $links_key );
					
					if ( $search1 === false && $search2 === false ) { continue; }
					
					//print_r( $link ); // チェック用
					
					$link_meta = get_post_meta( $link->ID );
					
					//print_r( $link_meta ); // チェック用

					$link_image = wp_get_attachment_url( get_post_thumbnail_id( $link->ID ) );

					if( $link_image == false && $link_meta[ '_olm_related_link_image_url' ][ 0 ] !== '' ) {
						$link_image = $link_meta[ '_olm_related_link_image_url' ][ 0 ];
					} else {
						$link_image = $this->get_olm_def_thumbnail_image();
					}
					
					if( $link_meta[ '_olm_related_link_background_image_repeat' ][ 0 ] === 'no-repeat' ) {
						$link_bgi_repeat = 'no-repeat';
					} elseif( $link_meta[ '_olm_related_link_background_image_repeat' ][ 0 ] === 'repeat' ) {
						$link_bgi_repeat = 'repeat';
					} elseif( $link_meta[ '_olm_related_link_background_image_repeat' ][ 0 ] === 'repeat-x' ) {
						$link_bgi_repeat = 'repeat no-repeat';
					} elseif( $link_meta[ '_olm_related_link_background_image_repeat' ][ 0 ] === 'repeat-y' ) {
						$link_bgi_repeat = 'no-repeat repeat';
					}
					
					
					if ( $link_meta[ '_olm_link_background_image_src' ][ 0 ] != '' ) {
						$return .= '<li 
							class="link-set-ul-li-with-background-image" 
							style="
								background-image: url( ' . $link_meta[ '_olm_link_background_image_src' ][ 0 ] . ' ); 
								background-size: ' . $link_meta[ '_olm_related_link_background_image_size_width' ][ 0 ] .'px ' . $link_meta[ '_olm_related_link_background_image_size_height' ][ 0 ] . 'px; 
								background-repeat: ' . $link_bgi_repeat . ';
								background-position: ' . $link_meta[ '_olm_related_link_background_image_position_row' ][ 0 ] . ' ' . $link_meta[ '_olm_related_link_background_image_position_column' ][ 0 ] . ';"
						>';
					} else {
						$return .= '<li class="link-set-ul-li">';
					}
					
					if( $link_meta[ '_olm_related_link_nofollow' ][ 0 ] != '' ) {
						$return .= '<a class="link-set-img-a" rel="nofollow" target="_blank" href="' . $link_meta[ '_olm_related_link_url' ][ 0 ] . '">';
					} else {
						$return .= '<a class="link-set-img-a" target="_blank" href="' . $link_meta[ '_olm_related_link_url' ][ 0 ] . '">';
					}
					
					if( $link_image != '' ) {
						$return .= '<div class="link-set-img-div" style="background-image: url(' . $link_image . '); width:100px; height:100px; background-size:100px 100px;"></div></a>';
					} else {
						$return .= '<div class="link-set-def-img-div" style="background-image: url(' . $link_image . '); width:100px; height:100px; background-size:100px 100px;"></div></a>';
					}
					
					$return .= '<div class="link-set-text-div">
							<p class="link-set-text-name-p">';
							
					if( $link_meta[ '_olm_related_link_nofollow' ][ 0 ] != '' ) {
						$return .= '<a class="link-set-text-name-a" rel="nofollow" target="_blank" href="' . $link_meta[ '_olm_related_link_url' ][ 0 ] . '">';
					} else {
						$return .= '<a class="link-set-text-name-a" target="_blank" href="' . $link_meta[ '_olm_related_link_url' ][ 0 ] . '">';
					}				
					$return .=  $link->post_title . '</a>
					</p>';
					
					$return .= '<p class="link-set-text-hatena-p">';
					
					if( $link_meta[ '_olm_related_link_hatena' ][ 0 ] != '' ) {		
						$return .= '<a href="http://b.hatena.ne.jp/entry/' . $link_meta[ '_olm_related_link_url' ][ 0 ] . '" class="hatena-bookmark-button" data-hatena-bookmark-title="' . $link->post_title . '" data-hatena-bookmark-layout="standard-balloon" data-hatena-bookmark-lang="ja" title="このエントリーをはてなブックマークに追加">
								<img src="' . OLM_PATH . 'images/entry-button/button-only@2x.png" alt="このエントリーをはてなブックマークに追加" width="16" height="16" />
							</a>';
					}
						
					$return .= '</p>';
					
					$return .= '<p class="link-set-text-description-p">' . strip_tags( $link->post_content ) . '</p>
						</div>
					</li>';
					
					$count++;
					
				}
				
				$return .= '</ul></div>';
				
				return $return;
				
			}

		#
		# アクション
		#
		function add_actions() {

			add_action( 'init', array( $this, 'add_post_type_olm_link_init' ) );

			if( is_admin() ) {

				add_action( 'delete_post', array( $this, 'olm_delete_related_link_fields' ) );

				add_action( 'admin_print_styles', array( $this, 'olm_enqueue_editor_styles' ) );
				add_action( 'admin_print_scripts', array( $this, 'olm_enqueue_editor_scripts' ) );
				add_action( 'admin_print_footer_scripts', array( $this, 'olm_add_shortcodes_quicktags' ) );

			}

			add_action( 'customize_register', array( $this, 'olm_customizer' ) );
			add_action( 'customize_preview_init', array( $this, 'olm_enqueue_theme_customizer' ) );

			add_action( 'wp_head', array( $this, 'olm_related_links_style' ), 11 );
			add_action( 'wp_enqueue_scripts', array( $this, 'olm_javascripts' ) );

		}
		// 「olm-link」を追加
			function add_post_type_olm_link_init() {
				
				$labels = array(
					'name'               => 'OLM Links',
					'singular_name'      => 'OLM Link',
					'menu_name'          => 'OLM Links',
					'name_admin_bar'     => 'OLM Link',
					'add_new'            => 'Add New',
					'add_new_item'       => 'Add New OLM Link',
					'new_item'           => 'New OLM Linkk',
					'edit_item'          => 'Edit OLM Link',
					'view_item'          => 'View OLM Link',
					'all_items'          => 'All OLM Links',
					'search_items'       => 'Search OLM Links',
					'parent_item_colon'  => 'Parent OLM Links:',
					'not_found'          => 'No OLM links found.',
					'not_found_in_trash' => 'No OLM links found in Trash.'
				);

				$args = array(
					'labels'              => $labels,
					'public'              => false,
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'show_ui'             => true,
					'show_in_menu'        => true,
					'query_var'           => false,
					'rewrite'             => array( 'slug' => 'olm-link' ),
					'capability_type'     => 'post',
					'has_archive'         => false,
					'hierarchical'        => false,
					'menu_position'       => null,
					'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'	)
				);

				register_post_type( 'olm-link', $args );
				
				// Add new taxonomy, make it hierarchical (like categories)
				$labels = array(
					'name'              => 'Groups',
					'singular_name'     => 'Group',
					'search_items'      => 'Search Groups',
					'all_items'         => 'All Groups',
					'parent_item'       => 'Parent Group',
					'parent_item_colon' => 'Parent Group:',
					'edit_item'         => 'Edit Group',
					'update_item'       => 'Update Group',
					'add_new_item'      => 'Add New Group',
					'new_item_name'     => 'New Group Name',
					'menu_name'         => 'Group',
				);

				$args = array(
					'hierarchical'      => true,
					'labels'            => $labels,
					'show_ui'           => true,
					'show_admin_column' => true,
					'query_var'         => false,
					'rewrite'           => array( 'slug' => 'olm-link-group' ),
				);

				register_taxonomy( 'olm-link-group', array( 'olm-link' ), $args );

			}


		// コンテンツが削除された場合の削除処理
			function olm_delete_related_link_fields( $post_ID ) {
				if( ! wp_is_post_revision( $post_ID ) ) {
					delete_post_meta( $post_ID, '_olm_related_link_cat' );
					delete_post_meta( $post_ID, '_olm_related_link_num' );
					delete_post_meta( $post_ID, '_olm_related_link_url' );
					delete_post_meta( $post_ID, '_olm_related_link_image_url' );
					delete_post_meta( $post_ID, '_olm_related_link_nofollow' );
					delete_post_meta( $post_ID, '_olm_related_link_hatena' );
				}
			}

		// エディター用のCSS・JSエンキュー
			function olm_enqueue_editor_styles() {
				
				wp_enqueue_style( 
					'olm_popup_box_style',
					OLM_PATH . 'tools/popup-box/popup-box.css'
				);
				
			}
			function olm_enqueue_editor_scripts() {
				
				wp_enqueue_script(
					'olm_popup_box_js', 
					OLM_PATH . 'tools/popup-box/popup-box.js',
					array( 'jquery' ),  //　必須の依存ファイル
					'',  // このスクリプトのバージョン（任意）
					true // フッターに出力する場合 true にします
				);
				
			}

		// HTMLエディターのクイックタグを追加
			function olm_add_shortcodes_quicktags() {
				
			    if ( wp_script_is( 'quicktags' ) ) { ?>

			    <script type="text/javascript">
					jQuery( document ).ready( function() {
							
						var canvasHtml, selection;
						
						// ショートコード「search_links」のクイックタグ
						QTags.addButton( 'search_links', 'SearchLinks', qt_search_links_insert_cb );
						function qt_search_links_insert_cb( element, canvas, ed, defaultValue ) {
							
							data = OLMSimplePopupBox( 'search_links' );
							
						}
						
						
					});
			    </script>
				<?php
			    }
				
			}

		// カスタマイザー
			function olm_customizer( $wp_customize ){

				$wp_customize->add_panel( 'olm_customize_setting_panel', array(
					'title' => 'Opt Link Manager設定',
					'priority' => 1
				) );

					// ビジュアルエディター用
					$wp_customize->add_section( 'olm_editor_settings_section', array(
						'title' => 'ビジュアルエディター用の表示設定',
						'priority' => 2,
						'panel' => 'olm_customize_setting_panel'
					));

						// ビジュアルエディター用の画像
							$wp_customize->add_setting( 'olm_search_links_mce_image', array(
								'default' => OLM_PATH . '/js/images/search_links.png',
								'capability' => 'edit_theme_options',
								'transport'=> 'postMessage',
								'sanitize_callback' => 'esc_url_raw',
							));
							$wp_customize->add_control( new WP_Customize_Image_Control(
								$wp_customize, 'olm_search_links_mce_image', array(
									'label' => 'リンク用背景画像',
									'section' => 'olm_editor_settings_section',
									'settings' => 'olm_search_links_mce_image',
									'priority' => 1
								)
							));

					// 出力用
					$wp_customize->add_section( 'olm_setting_section', array(
						'title' => '出力用の表示設定',
						'priority' => 2,
						'panel' => 'olm_customize_setting_panel'
					));
					
						// リンク用の背景画像
							$wp_customize->add_setting( 'olm_background_image', array(
								'default' => '',
								'type' => 'option',
								'capability' => 'edit_theme_options',
								'transport'=> 'postMessage',
								'sanitize_callback' => 'esc_url_raw',
							));
							$wp_customize->add_control( new WP_Customize_Image_Control(
								$wp_customize, 'olm_background_image', array(
									'label' => 'リンク用背景画像',
									'section' => 'olm_setting_section',
									'settings' => 'olm_background_image',
									'priority' => 1
								)
							));

						// 背景画像のサイズ
							$wp_customize->add_setting( 'olm_background_image_size', array(
								'default' => '100% 100%',
								'capability' => 'edit_theme_options',
								'transport'=> 'postMessage',
								'sanitize_callback' => 'sanitize_text_field', 
							));
							$wp_customize->add_control( 'olm_background_image_size', array(
								'section' => 'olm_setting_section',
								'settings' => 'olm_background_image_size',
								'label' => '左に横幅・右に縦幅',
								'type' => 'text',
								'priority' => 1,
							));	

						// 背景画像の位置
							$wp_customize->add_setting( 'olm_background_image_position_row', array(
							    'default'  => 'center',
								'capability' => 'edit_theme_options',
								'transport'=>'postMessage',
								'sanitize_callback' => 'sanitize_text_field', 
							));
							$wp_customize->add_control( 'olm_background_image_position_row', array(
								'section' => 'olm_setting_section',
								'settings' => 'olm_background_image_position_row',
								'label' => '背景画像の位置（行）',
								'type' => 'select',
								'priority' => 1,
								'choices' => array(
									'center' => '真ん中',
									'top' => '上',
									'bottom' => '下',
								),
							));	

							$wp_customize->add_setting( 'olm_background_image_position_column', array(
							    'default'  => 'center',
								'capability' => 'edit_theme_options',
								'transport'=>'postMessage',
								'sanitize_callback' => 'sanitize_text_field', 
							));
							$wp_customize->add_control( 'olm_background_image_position_column', array(
								'section' => 'olm_setting_section',
								'settings' => 'olm_background_image_position_column',
								'label' => '背景画像の位置（列）',
								'type' => 'select',
								'priority' => 1,
								'choices' => array(
									'center' => '真ん中',
									'left' => '左',
									'right' => '右',
								),
							));	

						// 背景画像の繰り返し
							$wp_customize->add_setting( 'olm_background_image_repeat', array(
							    'default'  => 'no-repeat',
								'capability' => 'edit_theme_options',
								'transport'=>'postMessage',
								'sanitize_callback' => 'sanitize_text_field', 
							));
							$wp_customize->add_control( 'olm_background_image_repeat', array(
								'section' => 'olm_setting_section',
								'settings' => 'olm_background_image_repeat',
								'label' => '背景画像の繰り返し',
								'type' => 'select',
								'priority' => 1,
								'choices' => array(
									'no-repeat' => '繰り返しなし',
									'repeat' => '繰り返しあり',
									'repeat-x' => '横だけ繰り返しあり',
									'repeat-y' => '縦だけ繰り返しあり'
								),
							));	
						
						// リンク用のデフォルトサムネイル
							$wp_customize->add_setting( 'olm_def_thumbnail_image', array(
								'default' => '',
								'type' => 'option',
								'capability' => 'edit_theme_options',
								'transport'=>'postMessage',
								'sanitize_callback' => 'esc_url_raw',
							));
							$wp_customize->add_control( new WP_Customize_Image_Control(
								$wp_customize, 'olm_def_thumbnail_image', array(
									'label' => 'リンク用のデフォルトイメージ',
									'section' => 'olm_setting_section',
									'settings' => 'olm_def_thumbnail_image',
									'priority' => 1
								)
							));

						// スタイルの変更
							$wp_customize->add_setting( 'olm_style_border', array(
							    'default'  => 'no-border',
								'capability' => 'edit_theme_options',
								'transport'=>'postMessage',
								'sanitize_callback' => 'sanitize_text_field', 
							));
							$wp_customize->add_control( 'olm_style_border', array(
								'section' => 'olm_setting_section',
								'settings' => 'olm_style_border',
								'label' => '外枠の種類',
								'type' => 'select',
								'priority' => 2,
								'choices' => array(
									'no-border' => '外枠なし',
									'border' => '外枠あり',
									'box-shadow' => '外枠あり（box-shadow）',
								),
							));	

							$wp_customize->add_setting( 'olm_style_border_radius_px', array(
								'default' => 0,
								'type' => 'theme_mod',
								'capability' => 'edit_theme_options',
								'transport'=>'postMessage',
								'sanitize_callback' => 'sanitize_int',
							));
							$wp_customize->add_control( 'olm_style_border_radius_px', array(
								'section' => 'olm_setting_section',
								'settings' => 'olm_style_border_radius_px',
								'label' => '外枠の角の丸み',
								'type' => 'range',
								'priority' => 3,
								'description' => '',
								'input_attrs' => array(
									'min' => 0,
									'max' => 150,
									'step' => 1,
									'value' => get_theme_mod('nav_menu_size', 0),
									'id' => 'olm_style_border_radius_px_id',
									'style' => 'width:100%;',
								),
							));

						// 配色
							$wp_customize->add_setting( 'olm_background_color', array( 
								'default' => '',
								'capability' => 'edit_theme_options',
								'transport'=>'postMessage',
								'sanitize_callback' => 'sanitize_text_field',
							));
						    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'olm_background_color', array(
								'label' => 'リンクの背景色',
								'section' => 'olm_setting_section',
								'settings' => 'olm_background_color',
								'priority' => 4,
						    )));
							
							$wp_customize->add_setting( 'olm_text_color', array( 
								'default' => '#000',
								'capability' => 'edit_theme_options',
								'transport'=>'postMessage',
								'sanitize_callback' => 'sanitize_hex_color',
							));
						    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'olm_text_color', array(
								'label' => 'リンクのテキストの色',
								'section' => 'olm_setting_section',
								'settings' => 'olm_text_color',
								'priority' => 5,
						    )));

							$wp_customize->add_setting( 'olm_text_link_color', array( 
								'default' => 'ffaa00',
								'capability' => 'edit_theme_options',
								'transport'=>'postMessage',
								'sanitize_callback' => 'sanitize_text_field',
							));
						    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'olm_text_link_color', array(
								'label' => 'リンクのテキストリンクの色',
								'section' => 'olm_setting_section',
								'settings' => 'olm_text_link_color',
								'priority' => 6,
						    )));

			}
			// テーマカスタマイザーJSのエンキュー
			function olm_enqueue_theme_customizer() {
				wp_enqueue_script(
					'olm_plugin_theme_customizer_js', 
					OLM_PATH . 'js/theme-customizer.js',
					array( 'jquery', 'customize-preview' ),  //　必須の依存ファイル
					'',  // このスクリプトのバージョン（任意）
					true // フッターに出力する場合 true にします
				);
			}


		// CSS・JSの出力
			function olm_related_links_style(){ ?>
				<style>
				.link-set,
				.e-content .link-set{
					margin: 0;
					padding: 0;	
				}
				.link-set-ul,
				.e-content .link-set-ul{

				/*	box-shadow: 0 0 5px; */
				}
				.link-set-ul-li,
				.e-content .link-set-ul-li{
					
					margin: 10px;
					padding: 10px;
					display: block !important;
					
					height: 120px;
					
					/*border: solid #ccc 1px; */

					overflow: hidden;
					
				<?php 
				echo $this->get_olm_background_image();
				echo $this->get_olm_background_size();
				echo $this->get_olm_background_position();
				echo $this->get_olm_background_repeat();

				echo $this->get_olm_background_color();
				echo $this->get_olm_border_style();
				echo $this->get_olm_border_radius();
				?>
					box-sizing: border-box;

				}
				.link-set-ul-li-with-background-image,
				.e-content .link-set-ul-li-with-background-image{
					padding: 10px;
					display: block !important;
					
					height: 100px;
					
				/*	border: solid #ccc 1px; */

					overflow: hidden;
				<?php 
				echo $this->get_olm_background_color();
				echo $this->get_olm_border_style();
				echo $this->get_olm_border_radius();
				?>
				}

				.link-set-img-a,
				.e-content .link-set-img-a{
					width: 100px;
					float: left;
				}
				.link-set-def-img-div,
				.e-content .link-set-def-img-div,
				.link-set-img-div,
				.e-content .link-set-img-div{
				<?php
					echo $this->get_olm_border_radius();
				?>
				}
				.link-set-text-div,
				.e-content .link-set-text-div{
					height: 100px;
					margin-left: 100px !important;
				}
				.link-set-text-name-p,
				.e-content .link-set-text-name-p{
					line-height: 2;
					height: 27px;
					overflow: hidden;
					margin: 0 !important;
					margin-left: 10px !important;
					margin-right: 10px !important;
					padding: 0 !important;
				<?php 
				echo $this->get_olm_text_color();
				?>
				}
				.link-set-text-name-a,
				.e-content .link-set-text-name-a,
				.link-set-text-name-a:link,
				.e-content .link-set-text-name-a:link,
				.link-set-text-name-a:visited,
				.e-content .link-set-text-name-a:visted{
				<?php 
				echo $this->get_olm_text_link_color();
				?>
				}
				.link-set-text-hatena-p,
				.e-content .link-set-text-hatena-p{
					height: 22px;
					
					margin: 0 !important;
					margin-left: 10px !important;
					margin-right: 10px !important;
					padding: 0 !important;
					
					border-bottom: solid #ccc 1px;
				<?php 
				echo $this->get_olm_text_color();
				?>

				}
				.link-set-text-description-p,
				.e-content .link-set-text-description-p{
					height: 50px;
					margin: 0 !important;
					margin-left: 10px !important;
					margin-right: 10px !important;
					padding: 0 !important;
				<?php 
				echo $this->get_olm_text_color();
				?>

				}

				.link-set-text-hatena-p iframe.hatena-bookmark-button-frame{
					top:-3px;
				}

				</style>
				<?php	
			}

			function olm_javascripts() {
				wp_enqueue_script(
					'hatena_bookmark_js',
					'https://b.st-hatena.com/js/bookmark_button.js',
					false
				);
			}


		#
		# フィルター
		#
		function add_filters() {
			add_filter( 'the_content', array( $this, 'olm_content_filter' ) );
		}

		// コンテンツ用フィルター
			function olm_content_filter( $content ) {
				
				global $post;

				if( esc_attr( get_post_meta( $post->ID, '_olm_text_link_on', true ) ) ) {

					$link_args = array(
						'post_type'      => 'olm-link',
						'posts_per_page' => -1,
						'orderby'        => 'ID',
						'order'          => 'ASC',
					);
					$links = get_posts( $link_args );
					unset( $link_args );

					foreach( $links as $link ) {
						$target_text = get_post_meta( $link->ID, '_olm_link_keyword', true );
						$target_url = get_post_meta( $link->ID, '_olm_related_link_url', true );
						$content = str_replace( $target_text, '<a rel="nofollow" target="_blank" href="' . $target_url . '">' . $target_text . '</a>', $content );
					} unset( $links );

					while( preg_match( '/(<a[^>]+>)(<a[^>]+>)([^<]+)(<\/a>)(<\/a>)/i', $content ) ) {
						$content = preg_replace(
							'/(<a[^>]+>)(<a[^>]+>)([^<]+)(<\/a>)(<\/a>)/i',
							'${1}${3}${5}',
							$content
						);
					}

				}
				
				$links_category = get_post_meta( $post->ID, '_olm_related_link_cat', true );
				$links_num = get_post_meta( $post->ID, '_olm_related_link_num', true );
				$links_orderby = get_post_meta( $post->ID, '_olm_orderby', true );
				
				$args = array(
					'links_category' => $links_category,
					'links_num'      => $links_num,
					'field'          => 'term_id',
					'orderby'        => $links_orderby
				);

				$content .= $this->olm_get_related_links( $args );
				
				return $content;

			}

		/*
		 * その他のメソッド
		*/
		// リンク取得メソッド
		function olm_get_related_links( $args ){
			
			extract( $args );
			
			if( $links_num == 0 ) { return; }
			
			$args = array(
				'orderby' => $links_orderby,
				'post_type' => 'olm-link',
				'posts_per_page' => $links_num,
				'tax_query' => array(
					array(
						'taxonomy' => 'olm-link-group',
						'field' => $field,
						'terms' => $links_category
					)
				)		
			);
			
			$link_collection = get_posts( $args );

			//print_r($link_collection); // チェック用
				
			$return = '<div class="link-set">
			<ul class="link-set-ul">';
			
			foreach ( $link_collection as $link ) {
				
				//print_r($link); // チェック用
				
				$link_meta = get_post_meta( $link->ID );
				
				//print_r($link_meta); // チェック用

				$link_image = wp_get_attachment_url( get_post_thumbnail_id( $link->ID ) );

				if( $link_image == false && $link_meta[ '_olm_related_link_image_url' ][ 0 ] !== '' ) {
					$link_image = $link_meta[ '_olm_related_link_image_url' ][ 0 ];
				} else {
					$link_image = $this->get_olm_def_thumbnail_image();
				}
				
				if( $link_meta[ '_olm_related_link_background_image_repeat' ][ 0 ] === 'no-repeat' ) {
					$link_bgi_repeat = 'no-repeat';
				} elseif( $link_meta[ '_olm_related_link_background_image_repeat' ][ 0 ] === 'repeat' ) {
					$link_bgi_repeat = 'repeat';
				} elseif( $link_meta[ '_olm_related_link_background_image_repeat' ][ 0 ] === 'repeat-x' ) {
					$link_bgi_repeat = 'repeat no-repeat';
				} elseif( $link_meta[ '_olm_related_link_background_image_repeat' ][ 0 ] === 'repeat-y' ) {
					$link_bgi_repeat = 'no-repeat repeat';
				}
				
				
				if ( $link_meta[ '_olm_link_background_image_src' ][ 0 ] != '' ) {
					$return .= '<li 
						class="link-set-ul-li-with-background-image" 
						style="
							background-image: url( ' . $link_meta[ '_olm_link_background_image_src' ][ 0 ] . ' ); 
							background-size: ' . $link_meta[ '_olm_related_link_background_image_size_width' ][ 0 ] . 'px ' . $link_meta[ '_olm_related_link_background_image_size_height' ][ 0 ] . 'px; 
							background-repeat: ' . $link_bgi_repeat . ';
							background-position: ' . $link_meta[ '_olm_related_link_background_image_position_row' ][ 0 ] . ' ' . $link_meta[ '_olm_related_link_background_image_position_column' ][ 0 ] . ';"
					>';
				} else {
					$return .= '<li class="link-set-ul-li">';
				}
				
				if( $link_meta[ '_olm_related_link_nofollow' ][ 0 ] != '' ) {
					$return .= '<a class="link-set-img-a" rel="nofollow" target="_blank" href="' . $link_meta[ '_olm_related_link_url' ][ 0 ] . '">';
				} else {
					$return .= '<a class="link-set-img-a" target="_blank" href="' . $link_meta[ '_olm_related_link_url' ][ 0 ] . '">';
				}
				
				if( $link_image != '' ) {
					$return .= '<div class="link-set-img-div" style="background-image: url(' . $link_image . '); width:100px; height:100px; background-size:100px 100px;"></div></a>';
				} else {
					$return .= '<div class="link-set-def-img-div" style="background-image: url(' . $link_image . '); width:100px; height:100px; background-size:100px 100px;"></div></a>';
				}
				
				$return .= '<div class="link-set-text-div">
						<p class="link-set-text-name-p">';
						
				if( $link_meta[ '_olm_related_link_nofollow' ][ 0 ] != '' ) {
					$return .= '<a class="link-set-text-name-a" rel="nofollow" target="_blank" href="' . $link_meta[ '_olm_related_link_url' ][ 0 ] . '">';
				} else {
					$return .= '<a class="link-set-text-name-a" target="_blank" href="' . $link_meta[ '_olm_related_link_url' ][ 0 ] . '">';
				}				
				$return .=  $link->post_title . '</a>
				</p>';
				
				$return .= '<p class="link-set-text-hatena-p">';
				
				if( $link_meta[ '_olm_related_link_hatena' ][ 0 ] != '' ) {		
					$return .= '<a href="http://b.hatena.ne.jp/entry/' . $link_meta[ '_olm_related_link_url' ][ 0 ] . '" class="hatena-bookmark-button" data-hatena-bookmark-title="' . $link->post_title . '" data-hatena-bookmark-layout="standard-balloon" data-hatena-bookmark-lang="ja" title="このエントリーをはてなブックマークに追加">
							<img src="' . OLM_PATH . 'images/entry-button/button-only@2x.png" alt="このエントリーをはてなブックマークに追加" width="16" height="16" />
						</a>';
				}
					
				$return .= '</p>';
				
				$return .= '<p class="link-set-text-description-p">' . strip_tags( $link->post_content ) . '</p>
					</div>
				</li>';
				
			}
			
			$return .= '</ul></div>';
			
			return $return;
		}


		#
		# カスタムCSS用
		#
			function get_olm_background_image() {
				return esc_url( 
					get_option( 'olm_background_image', false ) 
					? 'background-image: url(' . esc_url( get_option( 'olm_background_image' ) ) . ');' 
					: '' 
				);
			}

			function get_olm_background_size() {
				return 'background-size: ' . get_theme_mod( 'olm_background_image_size', 'contain' ) . ';';
			}

			function get_olm_background_position() {
				return 'background-position: ' . get_theme_mod( 'olm_background_image_position_row', 'center' ) . ' ' . get_theme_mod( 'olm_background_image_position_column', 'center' ) . ';';
			}

			function get_olm_background_repeat() {
				$repeat = get_theme_mod( 'olm_background_image_repeat', 'no-repeat' );
				if( $repeat == 'no-repeat' ) {
					return 'background-repeat: no-repeat;';
				} elseif( $repeat == 'repeat' ) {
					return 'background-repeat: repeat;';
				} elseif( $repeat == 'repeat-x' ) {
					return 'background-repeat: repeat no-repeat;';
				} elseif( $repeat == 'repeat-y' ) {
					return 'background-repeat: no-repeat repeat;';
				}
			}


			function get_olm_def_thumbnail_image() { // デフォルトのサムネイルイメージ
				return esc_url( 
					get_option( 'olm_def_thumbnail_image', false )
					? get_option( 'olm_def_thumbnail_image' )
					: OLM_PATH . 'images/no-img.png'
				);
			}

			function get_olm_border_style() { // 外枠の種類
				if( get_theme_mod('olm_style_border' ) == 'no-border' ) {
					return 'border: none;';
				} elseif( get_theme_mod( 'olm_style_border' ) == 'border') {
					return 'border: solid #666 1px;';
				} elseif( get_theme_mod( 'olm_style_border' ) == 'box-shadow' ) {
					return 'box-shadow: 0 0 5px;';
				}
			}

			function get_olm_border_radius() { // 外枠の角の丸み
				return 'border-radius: ' . get_theme_mod( 'olm_style_border_radius_px', 0 ) . 'px;';
			}

			function get_olm_background_color() { // ショートコードの背景色
				return 'background-color: ' . get_theme_mod( 'olm_background_color', false ) . ';';
			}

			function get_olm_text_color() { // ショートコードのテキストの色
				return 'color: ' . get_theme_mod( 'olm_text_color', '#000' ) . ';';
			}

			function get_olm_text_link_color() { // ショートコードのテキストリンクの色
				return 'color: ' . get_theme_mod( 'olm_text_link_color', '#ffaa00' ) . ';';
			}

	}

	new Opt_Link_Magager();
}

?>