/**
 *  NewsletterGate admin script
 *
 *  @package NewsletterGate
 */

import './css/admin.scss';

(function($){
	$(
		function(){
			$( '.newslettergate-section .newslettergate-section-title' ).on(
				'click',
				function (){
					$( this ).parent().toggleClass( 'opened' );
				}
			);

			if ( $( '#newslettergateForm .color-picker' ).length ) {
				$( '#newslettergateForm .color-picker' ).wpColorPicker();
			}
		}
	);
})( jQuery );
