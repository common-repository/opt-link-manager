(function() {
		
	// ショートコードボタンの追加
    tinymce.PluginManager.add('olm', function( editor, url ) {
		
        var search_links_tag = 'search_links';
		
        //add popup
        editor.addCommand( 'search_links_popup', function( ui, v ) {
			
			console.log( ui );
			console.log( v );

            // 標準設定
            var key = '';
            if (v.key)
                key = v.key;
            var num = '';
            if (v.num)
                num = v.num;
            var order = 'DESC';
            if (v.order)
                order = v.order;
            var orderby = 'date';
            if (v.orderby)
                orderby = v.orderby;
				
            // ポップアップの設定
            editor.windowManager.open( {
                title: 'ショートコード「search_links」を挿入',
                body: [
                    {//add key input
                        type: 'textbox',
                        name: 'key',
                        label: 'キーワード',
                        value: key,
                        tooltip: '入力必須'
                    },
                    {//add num input
                        type: 'textbox',
                        name: 'num',
                        label: '表示件数（最大値）',
                        value: num,
                        tooltip: '半角数字で入力してください'
                    },
                    {//add order select
                        type: 'listbox',
                        name: 'order',
                        label: '昇順 or 降順',
                        value: order,
                        'values': [
                            {text: '降順（3, 2, 1; c, b, a）', value: 'DESC'},
                            {text: '昇順（1, 2, 3; a, b, c）', value: 'ASC'}
                        ],
                        tooltip: '並びの順を選択してください。'
                    },
                    {//add orderby select
                        type: 'listbox',
                        name: 'orderby',
                        label: '並び方',
                        value: orderby,
                        'values': [
                            {text: '投稿ID順', value: 'ID'},
                            {text: '著者順', value: 'author'},
                            {text: 'タイトル順', value: 'title'},
                            {text: '日付順', value: 'date'},
                            {text: '更新日順', value: 'modified'},
                            {text: '親ID順', value: 'parent'},
                            {text: 'ランダム順', value: 'rand'},
                            {text: 'コメント数順', value: 'comment_count'}
                        ],
                        tooltip: '並び方を選択してください。'
                    }
                ],
                onsubmit: function( e ) { 
				
					if( e.data.key ) {
							
						// ショートコードの作成
						shortcode_str = '[' + search_links_tag + ' key="' + e.data.key + '"';
	 
						// numのチェック
						if (typeof e.data.num != 'undefined' && e.data.num.length)
							shortcode_str += ' num="' + e.data.num + '"';
						// orderのチェック
						if (typeof e.data.order != 'undefined' && e.data.order.length)
							shortcode_str += ' order="' + e.data.order + '"';
						// orderbyのチェック
						if (typeof e.data.orderby != 'undefined' && e.data.orderby.length)
							shortcode_str += ' orderby="' + e.data.orderby + '"]';
	 
						// ショートコードの挿入
						editor.insertContent( shortcode_str );
						
					}
					
					// Stringの削除
					delete shortcode_str;
					
                }
            });
        });
 
        //add button
        editor.addButton( 'search_links', {
            icon: 'search_links',
            tooltip: 'ショートコード「search_links」を挿入',
            onclick: function() {
                editor.execCommand('search_links_popup','',{
                    key : '',
                    num : '',
                    order   : 'DESC',
                    orderby: 'date'
                });
            }
        });

        // イメージをダブルクリック時にショートコードを編集する
        editor.on( 'DblClick', function(e) {
			
            //console.log( e.target );
			
            /*
            var cls = e.target.className.indexOf( 'wp-related_links' );
            var cls = e.target.className.indexOf( 'wp-search_links' );
            */
			
            if ( e.target.nodeName == 'IMG' && e.target.className.indexOf( 'wp-related_links' ) > -1 ) {
				
                var slug = e.target.attributes['data-sh-attr'].value;
                slug = window.decodeURIComponent(slug);
                console.log( slug );
                editor.execCommand('related_links_popup','',{
                    slug : getAttr( slug, 'slug' ),
                });
				
            } else if ( e.target.nodeName == 'IMG' && e.target.className.indexOf('wp-search_links') > -1 ) {
				
                var attr = e.target.attributes['data-sh-attr'].value;
                attr = window.decodeURIComponent(attr);
                console.log(attr);
                editor.execCommand('search_links_popup','',{
                    key : getAttr( attr,'key' ),
                    num : getAttr( attr,'num' ),
                    order   : getAttr( attr,'order' ),
                    orderby: getAttr( attr,'orderby' )
                });
				
            }

        });

        // 該当するショートコードを特定のイメージに変換する。（ダブルクリックで編集可能）
        editor.on( 'BeforeSetcontent', function( event ){
            event.content = replaceShortcodes( event.content );
        });
 
        // 該当するイメージタグの属性値からショートコードに変換する。
        editor.on( 'GetContent', function( event ){
            event.content = restoreShortcodes(event.content);
        });
		
		
    /* 以下、ツール */
		
        // 属性を取得する関数
        function getAttr(s, n) {
            n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
            return n ?  window.decodeURIComponent(n[1]) : '';
        };

		// 画像変換用のIMGタグを返す関数
        function html( htmlClass, data ) {
			
			// ショートコードを置き換える画像ファイル
			if( htmlClass == 'wp-search_links' ) {
				
            	var placeholder = jQuery( '#search_links_img' ).attr( 'data-img' );
				
			}
			
			// HTMLのimgタグに挿入する属性値に
            data = window.encodeURIComponent( data );
			
            /*
            if( htmlContent ) {
            	content = window.encodeURIComponent( htmlContent );
			}
            */
			
            //console.log( data ); // 「 slug="abc"」変換前
            //console.log( data ); // 「%20slug%3D%22abc%22」変換後
			
            return '<img src="' + placeholder + '" class="mceItem ' + htmlClass + '" ' + 'data-sh-attr="' + data /* + '" data-sh-content="' + content */+ '" data-mce-resize="false" data-mce-placeholder="1" />';
			
        }
 		
		// 該当するコンテンツを画像ファイルに変換する
        function replaceShortcodes( content ) {
			
			// search_links
            content = content.replace( /\[search_links([^\]]*)\]/g, function( all, attr ) {
                return html( 'wp-search_links', attr );
            });
			
            //console.log( content ); // チェック用
			
			return content;
        }
 		
		// IMGタグからショートコードに変換する関数
        function restoreShortcodes( content ) {
			
            return content.replace( /(?:<p(?: [^>]+)?>)*(<img [^>]+>)(?:<\/p>)*/g, function( match, image ) {
				
                var data = getAttr( image, 'data-sh-attr' );
				var con = getAttr( image, 'data-sh-content' ); // コンテンツ用
 				
                //console.log( data );
				
                if( data.indexOf( 'key', 0 ) != -1 ) {
					
					return '[' + search_links_tag + data + ']';
					
                }
				
                return match;
				
            });
			
        }

    });
	
})();