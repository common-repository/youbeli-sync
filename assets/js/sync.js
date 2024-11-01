jQuery( document ).ready(
	function($){

		jQuery( '#sync-continue' ).click(
			function(){
				jQuery( '#do_action' ).val( jQuery( this ).attr( 'data-action' ) );
				jQuery( '#sync-form' ).attr( 'action',window.location.href );
				jQuery( '#sync-form' ).submit();
			}
		);

		jQuery( '#sync-selected' ).click(
			function(){
				jQuery( '#do_action' ).val( jQuery( this ).attr( 'data-action' ) );
				jQuery( '#sync-form' ).attr( 'action',window.location.href );
				jQuery( '#sync-form' ).submit();
			}
		);

		jQuery( '#sync-all' ).click(
			function(){
				jQuery( '#do_action' ).val( jQuery( this ).attr( 'data-action' ) );
				jQuery( '#sync-form' ).attr( 'action',window.location.href );
				jQuery( '#sync-form' ).submit();
			}
		);

		jQuery( '#unsync-selected' ).click(
			function(){
				alert( 'Confirm unsync selected product??' );
				jQuery( '#do_action' ).val( jQuery( this ).attr( 'data-action' ) );
				jQuery( '#sync-form' ).attr( 'action',window.location.href );
				jQuery( '#sync-form' ).submit();
			}
		);
	}
);
