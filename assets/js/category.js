jQuery( document ).ready(
	function($){
		var last_selected;
		var cat_id;
		var index;

		jQuery( '.popup' ).magnificPopup(
			{
				type : 'inline',
				callbacks: {
					elementParse: function(item) {
						cat_id    = item.el.data( 'woo-cat-id' );
						index     = item.index;
						var title = jQuery( '.cat-name:eq(' + item.index + ')' ).text();
						jQuery( '#select-title' ).text( 'Match category: ' + title );
					},
					open: function() {
						jQuery.magnificPopup.instance.close = function () {
							reset();
							jQuery.magnificPopup.proto.close.call( this );
						};
					}
				}
			}
		);

		jQuery( document ).on(
			'click','li[data-cat-id]',function(){
				var selected = jQuery( this );

				var item      = selected.closest( '.item' );
				var container = selected.closest( '#cat-container' );
				selected.siblings().removeClass( 'selected' );
				selected.addClass( 'selected' );

				var selected_id = selected.attr( 'data-cat-id' );
				last_selected   = selected_id;

				jQuery.ajax(
					{
						type: 'post',
						url: ajaxurl,
						dataType: 'json',
						data: {
							action: 'select',
							param: selected_id
						},
						success:function(data){
							if (data) {
								item.nextAll().remove();
								var cat_path = jQuery( '.selected' ).map(
									function() {
										return jQuery( this ).text();
									}
								).get();
								jQuery( '#cat-path' ).text( cat_path.join( ' > ' ) );

								if (data.length > 0) {
									var newItem = '<div class="item"></div>'
									container.append( newItem );
									item.next().find( 'ul' ).remove();
									var cat = '<ul>';
									jQuery.each(
										data, function(key,value){
											cat += '<li data-cat-id="' + value.id + '"' + ((value.child > 0) ? 'class="parent"' : '') + '>' + value.name + '</li>';
										}
									);
									cat += '</ul>';
									item.next().append( cat );
								}
							}
						},
						error: function(data){
							console.log( data );
						}
					}
				);
			}
		);

		jQuery( '#confirm' ).on(
			'click', function(){
				if (jQuery( '.selected' ).length > 1 || last_selected == '2012') {
					jQuery.ajax(
						{
							url: ajaxurl,
							data: {
								action: 'match_category',
								woo_id: cat_id,
								yb_id: last_selected
							},
							dataType: 'json',
							type: 'post',

							success: function(json){
								if (json.status) {
									var magnificPopup = jQuery.magnificPopup.instance;
									magnificPopup.close();
									jQuery( '.yb-cat-name:eq(' + index + ')' ).text( json.yb_cat );
								}
							}
						}
					);
				} else {
					alert( "Please select at least 2 categories" );
				}
			}
		);
	}
);
function reset() {
	jQuery( '#select-title' ).empty();
	jQuery( '#cat-path' ).empty();
	jQuery( '#cat-container .item' ).first().find( '.selected' ).removeClass( 'selected' );
	jQuery( '#cat-container .item' ).first().nextAll().remove();
}
