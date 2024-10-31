function OLMSimplePopupBox( type ){
	
	( function( $ ) {
		
		// 標準設定

		if( $( '#popupBox' ).size() == 0 ) {
			var popupBox = $( '<div id="popupBox"/>' );
			var windowShadow = $( '<div id="popupBox-shadow"/>' );
			$( windowShadow ).click( function( e ) {
				closePopupBox();
			});
			$( 'body' ).append( windowShadow );
			$( 'body' ).append( popupBox );
			
			console.log( 'empty box added!' );
			
		}
		
		$('#popupBox').empty();
		

		data = {};
		
		// 「タイトル」と「閉じるボタン」の追加
		$('#popupBox').append('<div id="popupBox-title">' + 'ショートコードの設定' + '</div>');
		$('#popupBox-title').append('<img id="closeImg" alt="close" style="float:right;" width="16" height="16" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAAAA3NCSVQICAjb4U/gAAAAGXRFWHRTb2Z0d2FyZQB3d3cuaW5rc2NhcGUub3Jnm+48GgAAAOdQTFRF////UFpatrq6uLy8UFpaUFpagYiIhIuLs7e3trq6oKamoaenmaGhmqGhm6Ghm6KiKDIyLjg4Mz09OEJCPkhIQ01NWGJiWWNjWmRkW2VlXWZmXWdnXmhoX2lpYmxsY21ta3Nza3R0bnZ2b3d3c3p6dX19eH9/fISEfYODho2NipSUi5WVkJaWprCwsLq6sry8tb+/tsDAuL+/ub+/ucLCucPDu8TEvMTEvMXFvcfHvsfHv8fHv8nJwcvLxM7Oxs7OyNLSy9XVzNbWzdXVz9nZ0Nra1N3d2OHh2uPj4Obm4efn8fT09Pb2nryFrQAAABB0Uk5TADxcXcfM1tbY2fn5+vr6+hAXMfYAAAClSURBVBgZXcFBTgJREEXRW/+XbQ8kQUlsTJoluP99uIYegAMMCdGoVfXEARPOgRtm/fmeq+/3dH99Ma60f/PHKYUwhGHTxocQubSZpeaODZ5JLSfEB5obcgqVOAKlgqYq7da6WO9UJUdQEqA0wAnqcOLimFNDTRF9lLZbaewRuP1abKw/0PPpE+F3aXZe1RdjOyO5H1YGAQT/9taG1sDAMKJ+uPUHWWdWhrbgEeoAAAAASUVORK5CYII%3D" />');
		$('#closeImg').click(function(e){
			closePopupBox();
		});			


		// タイプ別設定
		if( type == 'search_links' ) { // 「search_links」の処理
			
			// キーワード
			$( '#popupBox' ).append( '<label for="search_links_key"><strong>キーワード</strong></label><br />' );
			$( '#popupBox' ).append( '<input type="text" id="search_links_key" name="search_links_key" class="popupBox-text-field" /><br /><br />' );

			// 表示件数
			$( '#popupBox' ).append( '<label for="search_links_num"><strong>表示件数（最大値）</strong></label><br />' );
			$( '#popupBox' ).append( '<input type="text" id="search_links_num" name="search_links_num" class="popupBox-text-field" /><br /><br />' );
			
			// 並び方
			orderList = { "DESC": "降順（3, 2, 1; c, b, a）", "ASC": "昇順（1, 2, 3; a, b, c）" };
			$( '#popupBox' ).append( '<label for="search_links_order"><strong>並び方</strong></label><br />' );
			$( '#popupBox' ).append( '<select id="search_links_order" name="search_links_order" class="popupBox-select"></select><br /><br />' );
			$.map( orderList, function( val, i ) {
				$( '#search_links_order' ).append( '<option value="' + i + '">' + val + '</option>' );
			});
			
			// 並び順
			orderbyList = {
				"ID": "投稿ID順",
				"author": "著者順",
				"title": "タイトル順",
				"date": "日付順",
				"modified": "更新日順",
				"parent": "親ID順",
				"rand": "ランダム順",
				"comment_count": "コメント数順"
			};
			$( '#popupBox' ).append( '<label for="search_links_orderby"><strong>並び方</strong></label><br />' );
			$( '#popupBox' ).append( '<select id="search_links_orderby" name="search_links_orderby" class="popupBox-select"></select><br /><br />' );
			$.map( orderbyList, function( val, i ) {
				$( '#search_links_orderby' ).append( '<option value="' + i + '">' + val + '</option>' );
			});
			
			// 決定ボタン
			$( '#popupBox' ).append( '<a id="search_links_insert_button" class="search_links_insert_button button" href="javascript:void(0)">コードを挿入</a>' );
			
			// 表示位置のスタイル
			$('#popupBox').css('top', '100px');
			$('#popupBox').css('marginLeft', '-' + $('#popupBox').width() / 2 + 'px');
			$('#popupBox-shadow').css('height', $(document).height()+'px');
			
			// 表示（フェイドイン）
			$('#popupBox-shadow').fadeIn('fast', function(){
				$('#popupBox').fadeIn('fast');
			});
			
			// クリック時に
			$( '#search_links_insert_button' ).on( "click", function(){
				
				data = {
					"key": $( '#search_links_key' ).val(),
					"num": $( '#search_links_num' ).val(),
					"order": $( '#search_links_order' ).val(),
					"orderby": $( '#search_links_orderby' ).val()
				};
				

				insertSearchLinks( type, data );
				
				// ボックスを閉じる
				closePopupBox();
								
				
			});
			
		} else {
			return;
		}
		



	})( jQuery );
	
}

function closePopupBox(){
	
	(function($) {
		
		$('#popupBox').hide();
		$('#popupBox-shadow').fadeOut('slow');
		$('#popupBox').empty();
		
	})(jQuery);
	
}

function insertSearchLinks( type, data ) {
	
	(function($) {
	
		console.log( data );
		
		if( type == 'search_links' ) {

			if( data.key != '' ) {

				var searchLinksInsert = '[search_links key="' + data.key + '"';

				if( typeof( data.num ) != 'undefined' ) {
				 	searchLinksInsert += ' num="' + data.num + '"';
				}

				if( typeof( data.order ) != 'undefined' ) {
				 	searchLinksInsert += ' order="' + data.order + '"';
				}

				if( typeof( data.orderby ) != 'undefined' ) {
				 	searchLinksInsert += ' rel="' + data.orderby + '"';
				}


				searchLinksInsert += ']';
				
				delete data.key;
				delete data.num;
				delete data.order;
				delete data.orderby;

			}

			if( typeof( searchLinksInsert ) != 'undefined' ) {
				//$( '#content' ).text( searchPostInsert );
				edInsertContent(edCanvas, searchLinksInsert);
				console.log( searchLinksInsert );
			}


		}



		
	})(jQuery);
	
}
