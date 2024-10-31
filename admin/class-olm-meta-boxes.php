<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

// メタボックスの追加
if( ! class_exists( 'OLM_Meta_Boxes' ) ) {
	class OLM_Meta_Boxes {

		function __construct() {
			$this->init();
			$this->add_actions();
			$this->add_filters();
		}

		function init() {

		}

		function add_actions() {
			add_action( 'add_meta_boxes', array( $this, 'olm_related_link_add_custom_box' ) );
			add_action( 'add_meta_boxes', array( $this, 'olm_related_links_settings' ) );

			add_action( 'save_post', array( $this, 'olm_related_link_save_postdata' ) );
			add_action( 'save_post', array( $this, 'olm_related_link_save_linkdata' ) );
		}

			// 投稿・固定ページの "side" 画面にカスタムセクションを追加
			function olm_related_link_add_custom_box() {

				$screens = array( 'post', 'page' );

				foreach ( $screens as $screen ) {

					add_meta_box(
						'olm-related-link-category-box-id', // メタボックスのdivタグに与えるid
						'表示させたいリンクのカテゴリー', // メタボックスのタイトル
						array( $this, 'olm_related_link_box_for_post' ), // HTML出力するコールバック関数
						$screen, // 追加する「post_type」　カスタムポストタイプの場合はスラッグを使用する
						'side' // context
					);
				}
			}

		function olm_related_link_box_for_post( $post ) {
			
			// 認証に nonce を使う
			wp_nonce_field( 'olm_meta_box_for_links', 'olm_meta_box_nonce' );

			// データ入力用の実際のフォーム
			echo '<label for="olm_related_link_category">出力したいリンクのカテゴリー</label>';
			echo '<select id="olm_related_link_category" name="olm_related_link_category">';
			
			$value = get_post_meta( $post->ID, '_olm_related_link_cat', true );
			$terms = get_terms( 'olm-link-group' );
			if ( $terms ) {
				foreach( $terms as $term ) {
					if ( $term->count > 0 ) {
						echo '<option value="' . $term->term_id . '" ' . selected( esc_attr( $value ), $term->term_id, false ) . '>' . $term->name . '（リンク数： ' . $term->count . '）</option>';
					}
				}
			}
				
			echo '</select><br />';
			
			
			$orderby_value = get_post_meta( $post->ID, '_olm_orderby', true );
			if( empty( $orderby_value )  ) {
				$orderby_value = 'rand';
			}

			echo '<label id="olm_orderby">出力するリンクの並び順</label>';
			echo '<select id="olm_orderby" name="olm_orderby">';
			
			echo '<option value="id" ' . selected( esc_attr( $orderby_value ), 'id', false ) . '>投稿IDで並べ替え</option>';
			echo '<option value="author" ' . selected( esc_attr( $orderby_value ), 'author', false ) . '>記事の著者名で並べ替え</option>';
			echo '<option value="title" ' . selected( esc_attr( $orderby_value ), 'title', false ) . '>記事のタイトルで並べ替え</option>';
			echo '<option value="date" ' . selected( esc_attr( $orderby_value ), 'date', false ) . '>記事の投稿日で並べ替え</option>';
			echo '<option value="modified" ' . selected( esc_attr( $orderby_value ), 'modified', false ) . '>記事の更新日で並べ替え</option>';
			echo '<option value="parent" ' . selected( esc_attr( $orderby_value ), 'parent', false ) . '>投稿または固定ページの親のIDで並べ替え</option>';
			echo '<option value="rand" ' . selected( esc_attr( $orderby_value ), 'rand', false ) . '>ランダムに並べ替え</option>';
			echo '<option value="comment_count" ' . selected( esc_attr( $orderby_value ), 'comment_count', false ) . '>コメント数で並べ替え</option>';
			
			echo '</select><br />';
			
			
			echo '<label for="olm_related_link_number">出力したいリンクの数</label>';
			echo '<input type="text" id="olm_related_link_number" name="olm_related_link_number" value="'.get_post_meta( $post->ID, '_olm_related_link_num', true ) . '" size="2" /><br /><br />';

			// テキスト広告
			echo '<label for="olm_text_link_on">テキストリンクフィルター</label>';
			echo '<input type="checkbox" id="olm_text_link_on" name="olm_text_link_on" value="olm_text_link_on" ' . checked( esc_attr( get_post_meta( $post->ID, '_olm_text_link_on', true ) ), 'olm_text_link_on' , false ) . '" />';

			
		}



			function olm_related_link_save_postdata( $post_id ) {
				
				// Check if our nonce is set.
				if ( ! isset( $_POST['olm_meta_box_nonce'] ) ) {
					return;
				}

				// Verify that the nonce is valid.
				if ( ! wp_verify_nonce( $_POST['olm_meta_box_nonce'], 'olm_meta_box_for_links' ) ) {
					return;
				}

				// If this is an autosave, our form has not been submitted, so we don't want to do anything.
				if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
					return;
				}

				// Check the user's permissions.
				if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

					if ( ! current_user_can( 'edit_page', $post_id ) ) {
						return;
					}

				} else {

					if ( ! current_user_can( 'edit_post', $post_id ) ) {
						return;
					}
				}
				
				// Make sure that it is set.
				if ( ! isset( $_POST['olm_related_link_category'] ) && ! isset( $_POST[ 'olm_related_link_number' ] ) && ! isset( $_POST[ 'olm_orderby' ] ) ) {
					return;
				}

				// cat save
				$my_data = sanitize_text_field( $_POST['olm_related_link_category'] );
				update_post_meta( $post_id, '_olm_related_link_cat', $my_data );
				
				// num save
				$my_data = sanitize_text_field( $_POST['olm_related_link_number'] );
				update_post_meta( $post_id, '_olm_related_link_num', ( $my_data ? $my_data : 0 ) );

				$my_data = sanitize_text_field( $_POST['olm_orderby'] );
				update_post_meta( $post_id, '_olm_orderby', ( $my_data ? $my_data : 'rand' ) );

				// textads save
				$my_data = sanitize_text_field( $_POST['olm_text_link_on'] );
				update_post_meta( $post_id, '_olm_text_link_on', ( $my_data ? $my_data : false ) );
			}

			// 「olm-link」に追加するメタボックス 
			function olm_related_links_settings() {

				$screens = array( 'olm-link' );

				foreach ( $screens as $screen ) {

					add_meta_box(
						'olm-related-link-category-box-id',
						'リンクの設定',
						array( $this, 'olm_related_link_box_for_link' ),
						$screen,
						'advanced'
					);
				}
			}

		function olm_related_link_box_for_link( $post ) {
			
			// 認証に nonce を使う
			wp_nonce_field( 'olm_meta_box_for_links_data', 'olm_link_meta_box_nonce' );

			// データ入力用の実際のフォーム
			echo '<table class="form-table"><tbody>';
			
			echo '<tr>
			<th>
				<label for="olm_related_link_url">
					リンクのURL
				</label>
			</th>
			<td>
				<input class="large-text ui-autocomplete-input" type="text" id="olm_related_link_url" name="olm_related_link_url" value="' . get_post_meta( $post->ID, '_olm_related_link_url', true ) . '" />
			</td></tr>';

			echo '<tr>
			<th>
				<label for="olm_link_keyword">
					テキストフィルター用キーワード
				</label>
			</th>
			<td>
				<input class="large-text ui-autocomplete-input" type="text" id="olm_link_keyword" name="olm_link_keyword" value="' . get_post_meta( $post->ID, '_olm_link_keyword', true ) . '" />
			</td></tr>';

			echo '<tr>
			<th>
				<label for="olm_related_link_image_url">
					サムネイルで画像を設定しない場合の画像ファイルのURL<br />
					（サイト外のストレージから使用する場合など）
				</label>
			</th>
			<td>
				<input class="large-text ui-autocomplete-input" type="text" id="olm_related_link_image_url" name="olm_related_link_image_url" value="' . get_post_meta( $post->ID, '_olm_related_link_image_url', true ) . '" />
			</td></tr>';

			echo '<tr>
			<th>
				<label for="olm_related_link_nofollow">
					リンクの「rel」属性に「nofollow」を
				</label>
			</th>
			<td>
				<input type="checkbox" id="olm_related_link_nofollow" name="olm_related_link_nofollow" value="true" ' . checked( get_post_meta( $post->ID, '_olm_related_link_nofollow', true ), 'true', false ) .' />
			</td></tr>';

			echo '<tr>
			<th>
				<label for="olm_related_link_hatena">
					はてなブックマークのボタンを表示
				</label>
			</th>
			<td>
				<input type="checkbox" id="olm_related_link_hatena" name="olm_related_link_hatena" value="true" ' . checked( get_post_meta( $post->ID, '_olm_related_link_hatena', true ), 'true', false ) . ' />
			</td></tr>';

			echo '</tbody></table>';

		    echo '<p style="color: orange; font-size: 20px; text-align: center;">以下リンク用の背景画像の設定（オプション）</p>';


			echo '<table class="form-table"><tbody>';

			?>
			
			<tr>
		    	
				<th scope="row">
					<label for="olm_link_background_image_src">リンク出力時の背景画像</label>
				</th>
				<td>
					<div class="image-box">
						<?php if( get_post_meta( $post->ID, '_olm_link_background_image_src', true ) ) { ?>
							<img src="<?php echo get_post_meta( $post->ID, '_olm_link_background_image_src', true ); ?>" width="100" height="100" class="customaddmedia"/>
						<?php } ?>
					</div>
					<input type="hidden" id="olm_link_background_image_src" class="image regular-text" name="olm_link_background_image_src" value="<?php echo esc_attr( get_post_meta( $post->ID, '_olm_link_background_image_src', true ) ); ?>">
					<a href="#" class="button customaddmedia">画像を選ぶ</a>
		            <a href="#" class="button customresetmedia">画像をリセット</a>
				</td>
			</tr>
			<?php
			echo '<tr>
			<th>
				<label for="olm_related_link_background_image_size_width">
					背景画像のサイズ<br />
					（未入力の場合は「100%」になります。）
				</label>
			</th>
			<td>
				<p>横幅（単位：px）</p>
				<input class="large-text ui-autocomplete-input" type="text" id="olm_related_link_background_image_size_width" name="olm_related_link_background_image_size_width" value="' . get_post_meta( $post->ID, '_olm_related_link_background_image_size_width', true ) . '" style="width: 100px;" />
				<p>縦幅（単位：px）</p>
				<input class="large-text ui-autocomplete-input" type="text" id="olm_related_link_background_image_size_height" name="olm_related_link_background_image_size_height" value="' . get_post_meta( $post->ID, '_olm_related_link_background_image_size_height', true ) . '" style="width: 100px;" />
			</td></tr>';
			
			echo '<tr>
			<th>
				<label for="olm_related_link_background_image_position_row">
					出力する背景画像の位置（行）
				</label>
			</th>
			<td>
				<select id="olm_related_link_background_image_position_row" name="olm_related_link_background_image_position_row">
					<option value="center" ' . selected( get_post_meta( $post->ID, '_olm_related_link_background_image_position_row', true ), 'center', false ) . '>真ん中</option>
					<option value="top" ' . selected( get_post_meta( $post->ID, '_olm_related_link_background_image_position_row', true ), 'top', false ) . '>上</option>
					<option value="bottom" ' . selected( get_post_meta( $post->ID, '_olm_related_link_background_image_position_row', true ), 'bottom', false ) . '>下</option>
				</select>
			</td></tr>';

			echo '<tr>
			<th>
				<label for="olm_related_link_background_image_position_column">
					出力する背景画像の位置（列）
				</label>
			</th>
			<td>
			<select id="olm_related_link_background_image_position_column" name="olm_related_link_background_image_position_column">
				<option value="center" ' . selected( get_post_meta( $post->ID, '_olm_related_link_background_image_position_column', true ), 'center', false ) . '>真ん中</option>
				<option value="left" ' . selected( get_post_meta( $post->ID, '_olm_related_link_background_image_position_column', true ), 'left', false ) . '>左</option>
				<option value="right" ' . selected( get_post_meta( $post->ID, '_olm_related_link_background_image_position_column', true ), 'right', false ) . '>右</option>
			</select></td></tr>';

			echo '<tr>
			<th>
				<label for="olm_related_link_background_image_repeat">
					背景画像のリピート
				</label>
			</th>
			<td>
			<select id="olm_related_link_background_image_repeat" name="olm_related_link_background_image_repeat">
				<option value="no-repeat" ' . selected( get_post_meta( $post->ID, '_olm_related_link_background_image_repeat', true ), 'no-repeat', false ) . '>繰り返しなし</option>
				<option value="repeat" ' . selected( get_post_meta( $post->ID, '_olm_related_link_background_image_repeat', true ), 'repeat', false ) . '>繰り返しあり</option>
				<option value="repeat-x" ' . selected( get_post_meta( $post->ID, '_olm_related_link_background_image_repeat', true ), 'repeat-x', false ) . '>横だけ繰り返しあり</option>
				<option value="repeat-y" ' . selected( get_post_meta( $post->ID, '_olm_related_link_background_image_repeat', true ), 'repeat-y', false ) . '>縦だけ繰り返しあり</option>
			</select></td></tr>';

			echo '</tbody></table>';
			
			?>
			<script>
			( function( $ ) {
				$( document ).ready( function() {
					$( '.customaddmedia' ).click( function( e ) { /* トリガーの設定 */
						var $el = $( this ).parent();
						e.preventDefault();
						var uploader = wp.media({
							title: '画像の選択',
							button: {
								text: '画像を決定'	
							},
							multiple: true
						})
						.on( 'select', function() {
							var selection = uploader.state().get( 'selection' );
							/* 複数選択する場合
							var attachments = [];
							selection.map( function(attachment){
								attachment = attachment.toJSON();
								attachments.push(attachment.url);
							})
							$('div.image-box', $el).children().remove();
							attachments.forEach (function(element, index) {
								$('div.image-box', $el).append('<img src="'+ element +'" width="100" height="100" style="float:left;" class="images-from-library">');
							});
							$('input.image-urls', $el).val(attachments.join(','));
							*/
							
							/* 一枚だけ選択する場合 */
							var attachment = selection.first().toJSON();
							$( 'input.image', $el ).val( attachment.url );
							$( 'img.customaddmedia' ).attr( 'src', attachment.url );
							$( 'div.image-box', $el ).children().remove();
							$( 'div.image-box', $el ).append( '<img src="' + attachment.url + '" width="100" height="100" class="images-from-library">' );
						}).open();
						
					});
				
					$( '.customresetmedia' ).click( function( e ) { /* トリガーの設定 */
						e.preventDefault();
						var $el = $( this ).parent();
						$( 'input.image', $el ).val( '' );
						$( 'div.image-box', $el ).children().remove();
					});
				});
			}) ( jQuery );
			</script>
			<?php

		}

			function olm_related_link_save_linkdata( $post_id ) {
				
				// Check if our nonce is set.
				if ( ! isset( $_POST[ 'olm_link_meta_box_nonce' ] ) ) {
					return;
				}

				// Verify that the nonce is valid.
				if ( ! wp_verify_nonce( $_POST[ 'olm_link_meta_box_nonce' ], 'olm_meta_box_for_links_data' ) ) {
					return;
				}

				// If this is an autosave, our form has not been submitted, so we don't want to do anything.
				if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
					return;
				}

				// Check the user's permissions.
				if ( isset( $_POST[ 'post_type' ] ) && 'page' == $_POST[ 'post_type' ] ) {

					if ( ! current_user_can( 'edit_page', $post_id ) ) {
						return;
					}

				} else {

					if ( ! current_user_can( 'edit_post', $post_id ) ) {
						return;
					}
				}
				
				// Make sure that it is set.
				if ( ! isset( $_POST[ 'olm_related_link_url' ] ) && ! isset( $_POST[ 'olm_related_link_nofollow' ] )  && ! isset( $_POST[ 'olm_related_link_hatena' ] ) ) {
					return;
				}

				// url save
				$my_data = sanitize_text_field( $_POST[ 'olm_related_link_url' ] );
				update_post_meta( $post_id, '_olm_related_link_url', $my_data );
				
				// link-keyword save
				$my_data = sanitize_text_field( $_POST[ 'olm_link_keyword' ] );
				update_post_meta( $post_id, '_olm_link_keyword', $my_data );

				// image-url save
				$my_data = sanitize_text_field( $_POST[ 'olm_related_link_image_url' ] );
				update_post_meta( $post_id, '_olm_related_link_image_url', $my_data );

				// nofollow save
				$my_data = sanitize_text_field( $_POST[ 'olm_related_link_nofollow' ] );
				update_post_meta( $post_id, '_olm_related_link_nofollow', $my_data );

				// hatena save
				$my_data = sanitize_text_field( $_POST[ 'olm_related_link_hatena' ] );
				update_post_meta( $post_id, '_olm_related_link_hatena', $my_data );

				// backgroun image src save
				$my_data = sanitize_text_field( $_POST[ 'olm_link_background_image_src' ] );
				update_post_meta( $post_id, '_olm_link_background_image_src', $my_data );

				// backgroun image width save
				$my_data = sanitize_text_field( $_POST[ 'olm_related_link_background_image_size_width' ] );
				update_post_meta( $post_id, '_olm_related_link_background_image_size_width', $my_data );

				// backgroun image height save
				$my_data = sanitize_text_field( $_POST[ 'olm_related_link_background_image_size_height' ] );
				update_post_meta( $post_id, '_olm_related_link_background_image_size_height', $my_data );

				// backgroun image position row save
				$my_data = sanitize_text_field( $_POST[ 'olm_related_link_background_image_position_row' ] );
				update_post_meta( $post_id, '_olm_related_link_background_image_position_row', $my_data );

				// backgroun image position column save
				$my_data = sanitize_text_field( $_POST[ 'olm_related_link_background_image_position_column' ] );
				update_post_meta( $post_id, '_olm_related_link_background_image_position_column', $my_data );

				// backgroun image repeat save
				$my_data = sanitize_text_field( $_POST[ 'olm_related_link_background_image_repeat' ] );
				update_post_meta( $post_id, '_olm_related_link_background_image_repeat', $my_data );

			}

		function add_filters() {

		}

	}
}
?>