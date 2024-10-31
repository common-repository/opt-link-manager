<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

// 設定ページの追加
if( ! class_exists( 'OLM_Options_Page' ) ) {
	class OLM_Options_Page {
		
		function __construct() {

			add_action( 'admin_menu', array( $this, 'olm_options_menu' ) );

			// エクスポート
			add_action( 'wp_ajax_olm_ajax_export_links', array( $this, 'olm_ajax_export_links' ) );

			// リンク取得ツール関連
			add_action( 'wp_ajax_olm_get_target_posts', array( $this, 'olm_ajax_get_target_posts' ) );
			add_action( 'wp_ajax_olm_get_all_links', array( $this, 'olm_ajax_get_all_links' ) );
			add_action( 'wp_ajax_olm_filter_atag', array( $this, 'olm_ajax_filter_atag' ) );
			add_action( 'wp_ajax_olm_insert_link', array( $this, 'olm_ajax_insert_link' ) );

		}
		
		function olm_options_menu() {

			add_submenu_page( 
				'edit.php?post_type=olm-link',
				'Opt Link Manager ツール', 
				'Tools', 
				'update_plugins', 
				'olm_option', 
				array( $this, 'settings_page' ) 
			);

			/* 登録するリンクの設定 */
			register_setting( 'olm_options', 'get-links-from-posts' ); 
			register_setting( 'olm_options', 'set-olm-links-group' ); 
			
			/* 投稿記事の指定 */
			register_setting( 'olm_options', 'is-select-categories-on' ); 
			/*$terms = get_terms( 'category' );
			if ( $terms ) {
				foreach( $terms as $term ) {
					
					register_setting( 'olm_options', 'get-links-from-posts-' . $term->term_id ); 
					
				}
			}*/
			register_setting( 'olm_options', 'get-links-from-posts-cats' ); 
			register_setting( 'olm_options', 'select-post-tag-for-olm-link' ); 

		}

		function settings_page() {
			
			//print_r( $_POST ); // チェック用

			if( $_POST ) {

				//print_r( $_POST['import-csv-links'] ); // インポートチェック用

				if( $_POST[ 'import-csv-links' ] ) {

					if ( ! wp_verify_nonce( $_POST['import-csv-links-nonce'], 'import-csv-links' ) ) {

						echo '<div class="error"><p>error</p></div>';

					} else {

						if( ! $_FILES[ 'upload-csv-links-list' ] || ! current_user_can( 'manage_options' ) ) {

							echo '<div class="error"><p>error</p></div>';

						} else {

		                    //print_r($_FILES); // チェック用
							$file_data = file_get_contents( $_FILES[ 'upload-csv-links-list' ][ 'tmp_name' ] );
		                    //echo $file_data; // チェック用
							$this->make_olm_link_with_file_data( $file_data ); // 「Link」をCSVで登録（publish）
							
						}
					}
				}

			}
			
	        ?>
	        <div class="wrap">
	            <h1>Opt Link Manager オプション設定</h1>

				<div class="metabox-holder"><div id="general-settings-wrapper" class="settings-wrapper postbox">
					<h3 class="hndle">インポート・エクスポート</h2>
					<div class="inside"><div class="main">

			            <!-- CSVファイルのインポート -->
			            <form id="featured_upload" method="post" action="#" enctype="multipart/form-data">
			            	<table class="form-table">
			                	<thead>
			                    </thead>
			                    <tbody>
			                    
			                        <tr>
			                            <th scope="row"><label for="upload-csv-links-list">
			                                CSVファイルから「Link」をインポートする<br>
			                            </label></th>
			                            
			                            <td>
			                            	<p><small>
			                                   "リンクのタイトル","コンテンツ","リンクURL","画像URL","nofollow（true or false）","はてな（true or false）","キーワード","ステータス"
			                               </small></p>
			                                   <br>
			                                <p>
			                                   各アイテムは上記のようにシングルクオートもしくはダブルクオートで囲ってあっても大丈夫です。
			                                </p>
			                            	<input type="file" 
			                                	id="get-links-from-posts" 
			                                    name="upload-csv-links-list" 
			                                    accept="text/csv" 
			                                />
			                                <input type="hidden" name="post_id" id="post_id" value="55" />
											<?php wp_nonce_field( 'import-csv-links', 'import-csv-links-nonce' ); ?>
			    							<br>
											<input type="submit" id="import-csv-links" class="button" name="import-csv-links" value="CSVファイルをアップロード" />
			                            </td>
			                        </tr>
								</tbody>
			                </table>
			            </form>
	            
						<!-- ファイルのエクスポート -->
						<form action="<?php //echo OLM_PATH . 'export-csv.php'; ?>" method="post">
							<table class="form-table">
						    	<tbody>
						            <tr>
						                <th scope="row"><label for="export-csv-links">
						                    「Link」のCSVファイルでエクスポートする<br>
						                </label></th>
						                
						                <td>
						                	<p>
												カテゴリーを指定する際はチェックを入れてください。
						                    </p>
						                    <br>
											<?php 
												$terms = get_terms( 'olm-link-group' );
												if ( $terms ) {
													foreach( $terms as $term ) {
														if ( $term->count > 0 ) { ?>
											            	<label>
																<?php echo $term->name . '（リンク数： ' . $term->count . '）'; ?>
											                </label>
															<input type="checkbox"
											                	id="export-csv-cat-<?php echo $term->term_id; ?>]"
											                	class="csv-link-group"
											                	name="export-csv-cat[<?php echo $term->term_id; ?>]"
											                	value="<?php echo $term->term_id; ?>" 
											                />
											                <br>
											<?php		}
													}
												}
											?>
											<br>
											<?php wp_nonce_field( 'export-csv-links', 'export-csv-links-nonce' ); ?>
											<!--input type="submit" id="export-csv-links" name="export-csv-links" value="CSVファイルをダウンロード" /--> 
											<a id="export-csv-links" class="button csv-export-group" href="javascript:void(0);">CSVファイルを生成</a>
											<a id="generated-links-csv-download" class="button disabled" rel="download" href="javascript:void(0);" download="links-data.csv">CSVをダウンロード</a>
						                </td>
						            </tr>
						        	
						        </tbody>
						    </table>
						<script>
						( function( $ ) {

							// CSVエクスポート
							var linkGroupCSV = [];

							$( 'input.csv-link-group' ).change( function( e ) {

								changedValueCSV = $( this ).val();

								if( e.target.checked ) {
									linkGroupCSV[ changedValueCSV ] = changedValueCSV;
								} else {
									delete linkGroupCSV[ changedValueCSV ];
								}

								console.log( linkGroupCSV );

							});

							$( "#export-csv-links" ).click( function( e ) {
								var olm_export_nonce = $( "#export-csv-links-nonce" ).val();
								/*var exportCSV = $.ajax({
										action: 'olm_ajax_export_links',
										_wpnonce: olm_export_nonce,
										data: {
											"linkGroupCSV": linkGroupCSV
										}
									},
									function( resp ) {
										resp = JSON.parse( resp );
										//var dclass = 'error';
										console.log( resp );
									}
								);*/

								var exportCSV = $.ajax({
									type: "POST",
									url: "<?php echo admin_url( 'admin-ajax.php' ) ?>",
									context: this,
									//async: false,
									data: {
										"linkGroupCSV": linkGroupCSV,
										"_wpnonce": olm_export_nonce,
										action: "olm_ajax_export_links"
									},
									error: function( jqHXR, textStatus, errorThrown ) {
										console.log( textStatus );
									},
									success: function( data, textStatus, jqHXR ) {
										//console.log( data );
										exportCSVjson = JSON.parse( data );
										//console.log( exportCSVjson );
										if( exportCSVjson.match( /https?/ ) ) {
											$( '#generated-links-csv-download' ).attr( 'href', exportCSVjson );
											$( '#generated-links-csv-download' ).removeClass( 'disabled' );
										} else {
											'error'
										}
										data = textStatus = jqHXR = null;
									}
								})

							});

							$( '#generated-links-csv-download' ).click( function( e ) {
								$( '#generated-links-csv-download' ).addClass( 'disabled' );
							});

						}) ( jQuery );
						</script>
						</form>

					</div></div>
			    </div></div>

				<div class="metabox-holder"><div id="general-settings-wrapper" class="settings-wrapper postbox">
					<h3 class="hndle">リンク取得ツール</h2>
					<div class="inside"><div class="main">
	            
						<!-- 投稿ページから取得 -->
						<!--form id="goption" method="post" action="options.php"-->
						    <?php 
						    settings_fields( 'olm_options' );
						    do_settings_sections( 'olm_options' );
						    ?>
						    <table class="form-table">

						        <tbody>

						            <tr>
						                <th scope="row"><label for="set-olm-links-group">
						                    「Link」のグループを設定する
						                </label></th>
						                
						                <td>
						                	<p><small>
						                    	下でチェックした「リンクを取得するカテゴリー」の設定を反映させます。<br>
						                        
						                    	※ 指定しない場合は全ての投稿から取得します。<br>
						                        
						                    </small></p>
						                	<select  
						                    	id="set-olm-links-group" 
						                        name="set-olm-links-group"
						                    >
						                    	<option
						                            value="no-group" 
						                            <?php //selected( get_option( 'set-olm-links-group', 'no-group' ), 'no-group' ); ?>
						                        >
						                        	「Group」は設定しない
						                        </option>
												<?php 
													$terms = get_terms( 'olm-link-group', array( 'hide_empty' => false ) );
													if ( $terms ) {
														foreach( $terms as $term ) {
															//if ( $term->count >= 0 ) { ?>
																<option 
												                	value="<?php echo $term->term_id; ?>" 
																	<?php //selected( get_option( 'set-olm-links-group', false ), $term->term_id ); ?>
												                >
																	<?php echo $term->name . '（リンク数： ' . $term->count . '）'; ?>
												                </option>
															<?php //}
														}
													}
												?>
						                    </select>
						                </td>
						            </tr>

						            <tr>
						                <th scope="row">
						                    リンクを取得するカテゴリー
						                </th>
						                
						                <td>
						                
											<?php 
												//$cats_options_array = get_option( 'get-links-from-posts-cats', false );
												//print_r( $cats_options_array );
												$terms = get_terms( 'category' );
												if ( $terms ) {
													foreach( $terms as $term ) {
														if ( $term->count > 0 ) { ?>
						                
															<input type="checkbox" 
																id="get-links-from-posts-cats-<?php echo $term->term_id; ?>" 
																class="regular-checkbox get-links-from-posts" 
															    name="get-links-from-posts-cats[<?php echo $term->term_id; ?>]" 
															    value="<?php echo $term->term_id; ?>" 
																<?php //checked( $cats_options_array[ $term->term_id ], $term->term_id ); ?> 
															>
															<label>
																<?php echo $term->name . '（投稿数： ' . $term->count . '）'; ?>
															</label>
															<br>
														<?php }
													}
												}
											?>
						                </td>
						            </tr>
						            
						            <tr>
						                <th scope="row"><label for="select-post-tag">
						                    タグを指定する
						                </label></th>
						                
						                <td>
						                	<p><small>
						                    	下でチェックした「リンクを取得するカテゴリー」の設定を反映させます。<br>
						                        
						                    	※ 指定しない場合は全ての投稿から取得します。
						                    </small></p>
						                	<select  
						                    	id="select-post-tag-for-olm-link" 
						                        name="select-post-tag-for-olm-link"
						                    >
						                    	<option
						                            value="no-post-tag" 
						                            <?php selected( get_option( 'select-post-tag-for-olm-link', 'no-post-tag' ), 'no-post-tag' ); ?>
						                        >
						                        	「タグ」は設定しない
						                        </option>
												<?php 
													$terms = get_terms( 'post_tag' );
													if ( $terms ) {
														foreach( $terms as $term ) {
															if ( $term->count > 0 ) { ?>
																<option 
												                	value="<?php echo $term->term_id; ?>" 
																	<?php selected( get_option( 'select-post-tag-for-olm-link' ), $term->term_id ); ?>
												                >
																	<?php echo $term->name . '（投稿数： ' . $term->count . '）'; ?>
												                </option>
															<?php }
														}
													}
												?>
						                    </select>
						                </td>
						            </tr>

						        </tbody>
						        
						    </table>
							<p><small>
								「公開済み」「下書き」「ゴミ箱」に登録されているリンクの「タイトル」もしくは「URL」が同じ場合は取得されません。<br>
								また、属性により「download」を明記しているaタグ、href属性値にワードプレス関数によりURLでないと判断されるもの、<br>
								またはイメージファイルの場合も取得されません。
								<br><br>
								<strong>
									※ JavaScript必須です。ブラウザにより「OFF」にしている場合は使用できません。
									<br><br>
								</strong>
							</small></p>
						    <a id="olm_get_links" class="button" href="javascript:void(0);">リンクを取得</a>

						    <?php //submit_button();
						    //wp_enqueue_script( 'olm-ajax-js', plugins_url( '/js/olm.js', __FILE__ ), array( 'jquery' ) );
						    ?>
							<p id="message-inserted-saved">テキストのチェックが終わるまで操作できなくなります。</p>
							<p id="request-num-display"></p>
							<p id="exec-num-display"></p>
							<p id="request-num-display-2"></p>
							<p id="exec-num-display-2"></p>
							<p id="request-num-display-3"></p>
							<p id="exec-num-display-3"></p>
						<!--/form-->
					</div></div>
					<script>
						// リンク取得ツール
						var linkGroup, targetCats, targetTags, changedValue, targetPostsNum, json, targetLinksNum, requestNum;
						function escapeRegExp(string) {
						  return string.replace(/([.*+?^=!:${}()|[\]\/\\])/g, "\\$1");
						}
						( function( $ ) {
							$( document ).ready( function() {

								jQuery.fn.exists = function() {return Boolean(this.length > 0);}

								targetCats = [];

								//console.log( targetCats );
								$( 'select#set-olm-links-group, input.get-links-from-posts, select#select-post-tag-for-olm-link' ).change( function( e ) {

									e.preventDefault();
									changedValue = $( this ).val();

									if( e.target.id.match( 'set-olm-links-group' ) ) {
										linkGroup = changedValue;
										//console.log( linkGroup );
									} else if( e.target.id.match( 'get-links-from-posts-cats' ) ) {
										if( e.target.checked ) {
											targetCats[ changedValue ] = changedValue;
										} else {
											delete targetCats[ changedValue ];
										}
										//console.log( targetCats );
									} else if( e.target.id.match( 'select-post-tag-for-olm-link' ) ) {
										targetTags = changedValue;
										//console.log( targetTags );
									} else {
										return;
									}
									changedValue = null;
								} );

								$( "#olm_get_links" ).click( function( e ) {
									e.preventDefault();

									$( '#message-inserted-saved' ).text( 'コンテンツテキストを取得中（同期処理中なので操作できません。）' );
									
									$( '#request-num-display' ).empty();
									$( '#exec-num-display' ).empty();
									$( '#request-num-display-2' ).empty();
									$( '#exec-num-display-2' ).empty();

									$( '#inseted-data-log' ).empty();
									$( '#olm_get_links' ).addClass( 'disabled' );
									var ajaxLinks = $.ajax({
										type: "POST",
										url: "<?php echo admin_url( 'admin-ajax.php' ) ?>",
										context: this,
										//async: false,
										data: {
											action: "olm_get_all_links"
										},
										error: function( jqHXR, textStatus, errorThrown ) {
											console.log( textStatus );
										},
										success: function( data, textStatus, jqHXR ) {
											//console.log( data );
											if( data ) {
												linksArray = JSON.parse( data );
												//console.log( linksArray );
												data = textStatus = jqHXR = null;
												//$( '#message-inserted-saved' ).text( 'コンテンツテキストからリンクを検索中' );
											}
										}
									})
									// リンクを取得した後に処理
									.done( function() {

										$( '#message-inserted-saved' ).text( 'コンテンツテキストからリンクを検索中' );
										//console.log( linksArray );

										lastArray = [];
										var ajaxValue = $.ajax({
											type: "POST",
											url: "<?php echo admin_url( 'admin-ajax.php' ) ?>",
											context: this,
											//async: false,
											data: {
												"cats": targetCats,
												"tags": targetTags,
												action: "olm_get_target_posts"
											},
											
											error: function( jqHXR, textStatus, errorThrown ) {
												//console.log( textStatus );
											},
											success: function( data, textStatus, jqHXR ) {
												// JSONを取得
												jsonData = JSON.parse( data );

												// 不要なものを削除
												data = null;
												textStatus = null;
												jqHXR = null;

												// 対象記事の数
												targetPostsNum = jsonData.length;

												$( '#request-num-display' ).text( '対象記事の数：' + targetPostsNum );


												if( jsonData.length > 0 ) {
													targetLinksNum = 0;
													jsonDataLoop: for( var i = 0; i < jsonData.length; i++ ) {

														//item = jsonData[i].content.match( /<a([^>]+href\s*=\s*[\'"]([^\'"]+)[\'"][^>]*)>[^\`]+?<\/a>/gim );
														item = $( '<div />' ).html( jsonData[i].content ).text().match( /<a([^>]+href\s*=\s*[\'"]([^\'"]+)[\'"][^>]*)>[^\`]+?<\/a>/gim );
														
														// リンクの数
														//$( '#exec-num-display' ).text( '検索中のページ：' + i + ' / ' + targetPostsNum );


														if( item != null && item.length > 0 ) {
															itemLoop: for( var i2 = 0; i2 < item.length; i2++ ) {

																// 保存するリンク
																$( '#exec-num-display-2' ).text( '対象とするリンク：' + ( i2 + 1 ) + ' / ' + item.length );
																

																// 既存のデータかチェック
																for( var i3 = 0; i3 < linksArray.length; i3++ ) {
																	//console.log( escapeRegExp( item[ i2 ] ) );
																	//console.log( escapeRegExp( linksArray[ i3 ].title ) );
																	if( escapeRegExp( item[ i2 ].toString() ).match( escapeRegExp( linksArray[ i3 ].title.toString() ) ) ) {
																		continue itemLoop;
																	} else if( item[ i2 ].match( linksArray[ i3 ].url ) ) {
																		continue itemLoop;
																	}
																}

																// その他の精査
																	if( item[ i2 ].match( /rel\s*=\s*[\'"]download[\'"]/i ) ) {
																		continue itemLoop;
																	} else if( item[ i2 ].match( /download\s*=\s*[\'"][^\'"]+[\'"]/i ) ) {
																		continue itemLoop;
																	} else if( item[ i2 ].match( "<?php echo get_bloginfo( 'url' ); ?>" ) ) {
																		continue itemLoop;
																	} else if( item[ i2 ].match( /href\s*=\s*[\'"]https?:\/\/[^\s]+\.(jpg|jpeg|png|gif|svg|csv)[\'"]/ ) ) {
																		continue itemLoop;
																	}

																//最終チェック
																json = {};

																// URLチェック
																	var matchedHref = item[ i2 ].match( /href\s*=\s*[\"]([^\'"]+)[\'"]/i );
																	//console.log( matchedHref );
																	if( matchedHref[ 1 ] ) {
																		json.url = matchedHref[ 1 ];
																		//console.log( 'json' );
																		//console.log( json );
																		//console.log( json.url );
																	} else {
																		continue itemLoop;
																	}

																// aタグ内部の取得
																	var matchedInside = item[ i2 ].match( /<a[^>]+>([^_]+)<\/a>/i );
																	//console.log( 'aタグ内部' );
																	//console.log( matchedInside );
																	if( matchedInside == null ) {
																		continue itemLoop;
																	} else {
																		//console.log( 'aタグ内部' );
																		//console.log( matchedInside[ 1 ] );
																	}

																// 画像ファイルの取得
																	var imgTag = matchedInside[ 1 ].match( /<img[^>]+>/i );
																	//console.log( 'イメージタグ' );
																	//console.log( imgTag );
																	if( imgTag != null ) { // 存在する場合

																		// ソースを取得
																		imgSrc = imgTag[ 0 ].match( /src\s*=\s*[\'"]([^\'"]+)[\'"]/i );
																		json.img = imgSrc[ 1 ];
																		//console.log( 'イメージソース' ); // チェック用
																		//console.log( imgSrc[ 1 ] ); // チェック用

																		imgAlt = imgTag[ 0 ].match( /alt\s*=\s*[\'"]([^\'"]+)[\'"]/i );
																		//console.log( '代替テキスト' );
																		//console.log( imgAlt[ 1 ] );

																	}

																// テキストを取得
																matchedText = matchedInside[ 1 ].replace( /<[^>]+>/i, '' );
																// 「テキスト」と「代替テキスト」の両方が存在しない場合
																console.log( matchedText );
																//console.log( imgAlt[ 1 ] );
																// タイトルの取得
																title = ( matchedText != ""
																	? matchedText 
																	: ( imgAlt != null
																		? imgAlt[ 1 ]
																		: ""
																	)
																);

																if( title == "" ) {
																	continue itemLoop;
																} else {
																	json.title = title
																}


																//console.log( 'json2' ); // チェック用
																//console.log( json ); // チェック用

																// 処理する配列に加える
																lastArray.push( json );

																// 重複を避ける為、リンクリストに追加
																linksArray.push( json );

																// メッセージ用
																targetLinksNum++;
																$( '#request-num-display-2' ).text( '発見したリンクの数：' + targetLinksNum );

																// 削除
																json = matchedHref = matchedInside = matchedText = title = imgTag = imgSrc = imgAlt = null;



																/*
																// 最終チェック
																var ajaxValue2 = $.ajax({
																	type: "POST",
																	url: "<?php echo admin_url( 'admin-ajax.php' ) ?>",
																	context: this,
																	async: false,
																	data: {
																		"matched_atag": item[i2],
																		action: 'olm_filter_atag'
																	},
																	error: function( jqHXR, textStatus, errorThrown ) {
																		console.log( textStatus );
																	},
																	success: function( data, textStatus, jqHXR ) {
																		//console.log( data );
																		if( data ) {
																			targetLinksNum++;
																			$( '#request-num-display-2' ).text( '発見したリンクの数：' + targetLinksNum );

																			json = JSON.parse( data );
																			//console.log( json );
																			lastArray.push( json );

																			// チェックリストに追加
																			linksArray.push( json );
																			json = null;
																		}
																		data = textStatus = jqHXR = null;
																	},
																});
																*/
															}
															item = null;
														}
													}
													jsonData = null;
												}
											},
										})
										// チェックが終わってからの処理
										.done( function() {

											$( '#request-num-display-2' ).empty();
											$( '#exec-num-display-2' ).empty();


											$( '#message-inserted-saved' ).text( 'リンクデータを下書き保存中' );

											//console.log( linkGroup );
											//console.log( linksArray );
											//console.log( lastArray );

											// successの場合の数
											requestNum = targetLinksNum;
											execNum = 0;
											
											// 下書き保存
											if( requestNum >= 1 ) { for( var i = 0; i < lastArray.length; i++ ) {
												//console.log( lastArray[ i ].title );
												
												var ajaxValue3 = $.ajax({
													type: "POST",
													url: "<?php echo admin_url( 'admin-ajax.php' ) ?>",
													context: this,
													//async: false,
													data: {
														"group": linkGroup,
														"matched_title": lastArray[ i ].title,
														"matched_href": lastArray[ i ].url,
														"img_src": lastArray[ i ].img,
														action: 'olm_insert_link'
													},
													error: function( jqHXR, textStatus, errorThrown ) {
														//lastArray[i] = null;
														//console.log( jqHXR );
														//console.log( textStatus );
														//console.log( errorThrown );
													},
													success: function( data, textStatus, jqHXR ) {

														execNum++;
														$( '#exec-num-display' ).text( '処理済みリクエスト数：' + execNum + ' / ' + requestNum );
														if( execNum == requestNum ) {
															$( '#message-inserted-saved' ).text( '処理済みリクエスト数：' + execNum + '個のリンクの下書き保存が完了しました。保存したデータは「下書き保存ログ」で確認できます。' );
															$( '#olm_get_links' ).removeClass( 'disabled' );
														}

														//console.log( data );
														//console.log( textStatus );
														//console.log( jqHXR );
														insertedData = JSON.parse( data );
														insertedDataInfo = 'タイトル：' + insertedData.title + '<br>URL：' + insertedData.url;
														if( insertedData.img != '' ) { insertedDataInfo += '<br>イメージ：' + insertedData.img; }
														$( '#inseted-data-log' ).append( '<div class="inserted-data-info">' + insertedDataInfo + '</p>' );
														//console.log( insertedData );
														insertedData = insertedDataInfo = null;
													}
												})									
											} } else {
												$( '#message-inserted-saved' ).text( '登録するリンクは見つかりませんでした。' );
												$( '#olm_get_links' ).removeClass( 'disabled' );
											}
											lastArray = null;
										})
									});
								});
							});
						}) ( jQuery );
					</script>
				</div></div>

				<div class="metabox-holder"><div id="general-settings-wrapper" class="settings-wrapper postbox">
					<h3 class="hndle">下書き保存ログ</h2>
					<div class="inside"><div id="inseted-data-log" class="main">
						<!-- 下書き保存したリンクリスト -->
					</div></div>
				</div></div>



	        </div><?php
		}

		// アップロードしたCSVファイルで「Link」を作成（インポート）
		function make_olm_link_with_file_data( $file_data ) { 
			
			// 「olm-link」のURLを取得
			$args = array(
				'post_type'      => 'olm-link',
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft', 'trash' ),
			);
			$links = get_posts( $args );
			foreach( $links as $link ) {
				$link_title[] = $link->post_title;
				$link_url[] = $link->post_title;
			} unset( $links );


			$args = array( 'link_title', 'description', 'link_group', 'link_url', 'image_url', 'nofollow', 'hatena' );
			
			$row = 0;
			
			if( ( $handle = fopen( $_FILES['upload-csv-links-list']['tmp_name'], "r" ) ) !== FALSE ) {
				while( ( $link_data = fgetcsv( $handle, 0, "," ) ) !== FALSE ) {

		        	if( $link_data[ 0 ] == 'link_title' ) {
		        		$link_header = $link_data;
		        		continue;
		        	}
		        	
					// 既に存在する場合
		        	if( is_array( $link_title ) ) { if( in_array( $link_data[ 0 ], $link_title ) ) {
		        		continue;
		        	} }
		        	if( is_array( $link_url ) ) { if( in_array( $link_data[ 3 ], $link_url ) ) {
		        		continue;
		        	} }

					/* 各リンクを登録 */
					$insert_link_info_id = wp_insert_post(
						array(
							'post_type'    => 'olm-link',
							'post_title'   => $link_data[0],
							'post_content' => $link_data[1],
						)
					);

					if( $link_data[2] ) {
						if( preg_match_all( '/([^, ]+)/i', $link_data[2], $link_groups ) ) {
							foreach( $link_groups[ 0 ] as $link_group ) {
								if( $link_group )
									wp_set_object_terms( $insert_link_info_id, $link_group, 'olm-link-group', true );
							}
						}
					}

					update_post_meta( $insert_link_info_id, '_olm_related_link_url', $link_data[3] );
					update_post_meta( $insert_link_info_id, '_olm_related_link_image_url', $link_data[4] );	
					update_post_meta( $insert_link_info_id, '_olm_related_link_nofollow', $link_data[5] );
					update_post_meta( $insert_link_info_id, '_olm_related_link_hatena', $link_data[6] );
					if( $link_data[7] ) {
						update_post_meta( $insert_link_info_id, '_olm_link_keyword', $link_data[7] );	
					}
					if( $link_data[8] ) {
						wp_update_post( array(
							'ID' => $insert_link_info_id,
							'post_status' => $link_data[8],
						) );
					}
					$row++;

				}
				fclose($handle);
			}	
			
			if( $row ) {
				echo '<div class="updated">
					<p>'. $row .'個の「Link」を作成しました。</p>
				</div><br>
			    ';
			} else {
				echo '<div class="updated">
					<p>全て既に登録されています。</p>
				</div><br>
			    ';
			}

		}


		#
		# AJAX Functions
		#
		// CSVのエクスポート
		function olm_ajax_export_links() {

			//print_r( $_REQUEST );
			if( ! ( wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'export-csv-links' ) ) ) {
				wp_die( json_encode( 'nonce error' ) );
			}

			$link_args = array(
				'post_type'      => 'olm-link',
				'post_status'    => array( 'publish', 'draft', 'trash' ),
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			);

			if( isset( $_REQUEST[ 'linkGroupCSV' ] ) ) { $olm_link_group = $_REQUEST[ 'linkGroupCSV' ];	}
			
			if( isset( $olm_link_group ) ) { 
				$link_group = array();
				foreach( $olm_link_group as $index => $value ) {
					if( $value ) 
						$link_group[] = $value;
				}
				if( is_array( $link_group ) ) {
					$link_args['tax_query'] = array(
						array(
							'taxonomy' => 'olm-link-group',
							'field' => 'id',
							'terms' => $link_group
						)
					);
				}
			}

			//print_r( $link_args ); // チェック用
			
			$links = get_posts( $link_args );
			
			$csv_header = array(
				'link_title' => 'link_title',
				'description' => 'description',
				'link_group' => 'link_group',
				'link_url' => 'link_url',
				'image_url' => 'image_url',
				'nofollow' => 'nofollow',
				'hatena' => 'hatena',
				'keyword' => 'keyword',
				'link_status' => 'link_status'
			);

			//header('Content-Type: text/csv');
			//header('Content-Disposition: attachment; filename=olm-links.csv');
			
			$dir = wp_upload_dir();
			$file = $dir[ 'path' ] . '/links-data.csv';

			$stream = fopen( $file, 'w' ); // チェック用のコメントアウト
			fwrite( $stream, "\xef\xbb\xbf" );
			
			fputcsv( $stream, $csv_header ); // ヘッダーチェック
			
			$link_data = array();
			
			foreach( $links as $link ) {
				
				//print_r( $link ); // チェック用
				
				$link_cats = get_the_terms( $link->ID, 'olm-link-group' );

				//print_r( $link_cats ); // チェック用
				
				$link_cats_str = '';
				if( is_array( $link_cats ) ) {
					foreach( $link_cats as $link_cat ) {
						$link_cats_str .= $link_cat->name . ',';
					}
				} else {
					$link_cats_str = $link_cats;
				}
				$link_cats_str = trim( $link_cats_str, ',\s' );
				

				foreach ( $csv_header as $header_index => $header_name ) {
					
					if( $header_name == 'link_title' ) {
						$link_row[$header_name] = sanitize_text_field( $link->post_title );
					} elseif( $header_name == 'description' ) {
						$link_row[$header_name] = esc_textarea( $link->post_content );
					} elseif( $header_name == 'link_group' ) {
						$link_row[$header_name] = sanitize_text_field( $link_cats_str );
					} elseif( $header_name == 'link_url' ) {
						$link_row[$header_name] = sanitize_text_field( get_post_meta( $link->ID, '_olm_related_link_url', true ) );
					} elseif( $header_name == 'image_url' ) {
						$link_row[$header_name] = sanitize_text_field( get_post_meta( $link->ID, '_olm_related_link_image_url', true ) );
					} elseif( $header_name == 'nofollow' ) {
						$link_row[$header_name] = get_post_meta( $link->ID, '_olm_related_link_nofollow', true );
					} elseif( $header_name == 'hatena' ) {
						$link_row[$header_name] = get_post_meta( $link->ID, '_olm_related_link_hatena', true );
					} elseif( $header_name == 'keyword' ) {
						$link_row[$header_name] = sanitize_text_field( get_post_meta( $link->ID, '_olm_link_keyword', true ) );
					} elseif( $header_name == 'link_status' ) {
						$link_row[$header_name] = get_post_status( $link->ID );
					}
					
					$link_csv .= $link_row[$header_name] . ',';
					
				}
				
				$link_csv = trim( $link_csv, ',\s' );
				
				//print_r( $link_csv );
				
				fputcsv( $stream, $link_row ); // チェック用コメントアウト
				
			}
			
			fclose( $stream ); // チェック用コメントアウト

			wp_die( json_encode( $dir[ 'url' ] . '/links-data.csv' ) );

		}

		// リンク取得
		function olm_ajax_get_target_posts( $cats_ids = array(), $tag_id = false ) {
			
			// 
			if( isset( $_REQUEST[ 'cats' ] ) ) { $category_ids = $_REQUEST[ 'cats' ];	}
			if( isset( $_REQUEST[ 'tags' ] ) ) { $tag_id = $_REQUEST[ 'tags' ]; }

			$cat_ids = array();
			if( isset( $category_ids ) ) { foreach( $category_ids as $index => $value ) {
				$cat_ids[] = $value;
			} }

			// 投稿データを取得するクエリ
			$args = array(
				'post_type' => array( 'post' ),
				'posts_per_page' => -1,
				'post_status'    => array( 'publish' )
			);

			// カテゴリー指定
			if( count( $cat_ids ) > 0 ) {
				$args[ 'category__in' ] = $cat_ids;
			}
			// タグ指定
			if( $tag_id ) { $args[ 'tag_id' ] = $tag_id; }

			// 投稿データを取得
			$posts = get_posts( $args );
			unset( $args );

			// 投稿データのコンテンツを取得
			foreach( $posts as $post_key => $post ) {

				$data_posts[] = array(
					'title' => sanitize_text_field( $post->post_title ),
					'content' => esc_textarea( $post->post_content ),
				);
				
				//echo '<div id="' . $post_key . '" class="' . $post->post_title . '">' . $post->post_content . '</div>';
				
			} unset( $posts );

			wp_die( json_encode( $data_posts ) );
			
		}
		function olm_ajax_get_all_links() {
			// 「olm-link」のURLを取得
			$args = array(
				'post_type'      => 'olm-link',
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft', 'trash' ),
			);
			$links = get_posts( $args );
			unset( $args );

			$registered_link = array();
			if( $links ) {
				foreach( $links as $key => $link ) {
					//print_r( $link ); // チェック用
					$registered_link[] = array(
						'title' => sanitize_text_field( $link->post_title ),
						'url' => esc_url_raw( get_post_meta( $link->ID, '_olm_related_link_url', true ) )
					);
					
					// テスト環境用
					wp_delete_post( $link->ID ); 

				} unset( $links );
			}
			wp_die( json_encode( $registered_link ) );

		}
		function olm_ajax_filter_atag() {

			if( isset( $_REQUEST[ 'matched_atag' ] ) ) { 
				$matched_atag = str_replace( '\\', '', $_REQUEST[ 'matched_atag' ] );
			} else {
				//echo 'no atag';
				//wp_die();
			}

			$matched_title = '';
			$matched_href = '';
			$img_src = '';
			
			//echo $matched_atag; // チェック用

			// 「$matched_href」にhrefの値を取得
			if( preg_match( 
				'/href\s*=\s*[\"]([^\'"]+)[\'"]/i', 
				$matched_atag,
				$matched_href
			) ) {
				$matched_href = $matched_href[1];
			} else {
				wp_die();
			}

			
			// 以下、リンクが外部へのもので、「olm-link」に登録されていない場合

			//echo $matched_atag . PHP_EOL; // チェック用

			// 「$matched_inside」にaタグで囲っているHTMLを取得
			// imgタグがある場合
			if( preg_match( 
				'/<a[^>]+>([^_]+)<\/a>/i', 
				$matched_atag,
				$matched_inside
			) ) {
				//unset( $matched_atag );
				$matched_inside = $matched_inside[1];
			} else {
				wp_die();
			}

			if( preg_match( '/<img[^>]+>/i', $matched_inside, $img_tag ) ) {

				// srcの値を取得
				preg_match(
					'/src\s*=\s*[\'"]([^\'"]+)[\'"]/i',
					$matched_inside,
					$img_src
				);
				$img_src = $img_src[1] ? $img_src[1] : '';
				//echo $img_src . PHP_EOL;

				// altの値を取得
				preg_match(
					'/alt\s*=\s*[\'"]([^\'"]+)[\'"]/i',
					$matched_inside,
					$img_alt
				);
				$img_alt = $img_alt[1] ? $img_alt[1] : '';

				unset( $img_tag );
				
			} 

			//echo $matched_inside . PHP_EOL; // チェック用

			
			// テキストのみを取得
			$matched_title = strip_tags( $matched_inside );
			unset( $matched_inside );
			$matched_title = preg_replace(
				'/(\n|\r)/i',
				'',
				$matched_title
			);
			
			// テキストがない場合
			if( ! preg_match( '/[^\s]+/i', $matched_title ) ) {
				$matched_title = $img_alt ? $img_alt : '';
			} unset( $img_alt );

			if( $matched_title == '' ){
				wp_die();
			}

			
			// サニタイズ
			$matched_title = sanitize_text_field( trim( $matched_title ) );
			$matched_href = esc_url_raw( $matched_href );
			$img_src = esc_url_raw( $img_src );

			if( ! $matched_href ) {
				wp_die();
			}

			// 最終チェック用
			//echo $matched_title . "\n";
			//echo $matched_href . "\n";
			//echo $img_src . "\n";
			$return = array(
				'title' => $matched_title,
				'url' => $matched_href,
				'img' => $img_src
			);
			
			wp_cache_flush();

			wp_die( json_encode( $return ) );

		}
		function olm_ajax_insert_link() {

			if( isset( $_REQUEST[ 'matched_title' ] ) ) { $matched_title = $_REQUEST[ 'matched_title' ]; }
			if( isset( $_REQUEST[ 'matched_href' ] ) ) { $matched_href = $_REQUEST[ 'matched_href' ]; }
			if( isset( $_REQUEST[ 'img_src' ] ) ) { $img_src = $_REQUEST[ 'img_src' ]; }
			if( isset( $_REQUEST[ 'group' ] ) ) { 
				$link_group = $_REQUEST[ 'group' ]; 
			} else {
				$link_group = 'no-group';
			}

			// サニタイズ
			$matched_title = sanitize_text_field( $matched_title );
			$matched_href = esc_url_raw( $matched_href );
			$img_src = esc_url_raw( $img_src );

			$return = array(
				'title' => $matched_title,
				'url' => $matched_href,
				'img' => $img_src
			);
			
			//wp_die( json_encode( $return ) );

			// 「$matched_title」をタイトルにし、「olm-link」を新規追加する
			$insert_link_info_id = wp_insert_post(
				array(
					'post_type'  => 'olm-link',
					'post_title' => $matched_title
				)
			);
			//unset( $matched_title );

			//echo $insert_link_info_id . "\n"; // チェック用
			//echo get_option( 'set-olm-links-group' );

			if( isset( $insert_link_info_id ) ) {
				update_post_meta( $insert_link_info_id, '_olm_related_link_url', $matched_href );
				if( $img_src != '' ) {
					update_post_meta( $insert_link_info_id, '_olm_related_link_image_url', $img_src );
				}
				if( $link_group != 'no-group' ) {
					wp_set_post_terms( $insert_link_info_id, $link_group, 'olm-link-group', false );
				}
			}
			//$wpdb->flush();

			unset( $insert_link_info_id, $matched_href, $img_src, $matched[ 0 ][ $matched_key ], $matched_key );
			//wp_cache_flush();
			//echo memory_get_usage();
			//echo PHP_EOL;
			wp_cache_flush();

			wp_die( json_encode( $return ) );

		}

	} // クラスの終わり
}


?>