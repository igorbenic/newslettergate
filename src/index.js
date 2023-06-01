/**
 *  NewsletterGate front script
 *
 *  @package NewsletterGate
 */

import './css/shortcode.scss';

function NewsletterGate($){
	this.init = function init() {
		$( document ).on(
			'submit',
			'.newslettergate-form',
			function (e){
				e.preventDefault();

				var $this = $( this ),
				self      = this,
				container = $this.parents( '.newslettergate' );

				container.addClass( 'newslettergate-loading' );
				$.ajax(
					{
						method: 'POST',
						url: newslettergate.ajax,
						data: {
							nonce: newslettergate.nonce,
							data: parseString( $( this ).serialize() ),
							action: $( this ).find( '[name=ng_action]' ).val()
						},
						success: function( resp ) {
							if ( ! resp.success ) {
								if ( ! resp.data ) {
									alert( resp.data );
								}
								return;
							}

							if ( ! resp.data ) {
								return;
							}

							if ( resp.data.reload ) {
								window.location.reload();
								return;
							}

							if ( resp.data.html ) {
								container.replaceWith( resp.data.html );
							}
						},
						error: function ( er ) {
							console.log( er );
						},
						complete: function () {
							container.removeClass( 'newslettergate-loading' );
						}
					}
				)

			}
		);
	}

	var parseString = function parseString(queryString) {
		var params      = {};
		var queries     = queryString.split( '&' );
		var queryLength = queries.length;

		for (var i = 0; i < queryLength; i++) {
			var temp  = queries[i].split( '=' );
			var key   = decodeURIComponent( temp[0] );
			var value = decodeURIComponent( temp[1] );

			// Handle key-value pairs without values (e.g., "param" instead of "param=value").
			if (typeof value === 'undefined') {
				value = '';
			}

			var hasBracket = key.search( "]" ) > 0;

			// Check if the key contains array syntax, e.g., checks[s].
			var match = key.match( /^([^\[\]]+)(\[([^\[\]]*)\])*$/ );
			if (match && hasBracket) {
				var mainKey = match[1];
				var subKey  = match[3];

				if (subKey === '' || 'undefined' === subKey) {
					// If the subKey is empty, treat it as an array without keys.
					if ( ! params.hasOwnProperty( mainKey )) {
						params[mainKey] = [];
					}

					params[mainKey].push( value );
				} else {
					// If the subKey is present, treat it as a nested object.

					// Check if the mainKey already exists.
					if ( ! params.hasOwnProperty( mainKey )) {
						params[mainKey] = {};
					}

					// Check if the subKey already exists.
					if (params[mainKey].hasOwnProperty( subKey )) {
						if (Array.isArray( params[mainKey][subKey] )) {
							// If the subKey already has an array value, push the new value.
							params[mainKey][subKey].push( value );
						} else {
							// If the subKey has a single value, convert it to an array and add the new value.
							params[mainKey][subKey] = [params[mainKey][subKey], value];
						}
					} else {
						// If the subKey doesn't exist, create it and assign the value.
						params[mainKey][subKey] = value;
					}
				}
			} else {
				// If the key doesn't contain array syntax, process it normally.
				params[key] = value;
			}
		}

		return params;
	}

	return this;
}

(function ($){
	$(
		function(){
			var ng = new NewsletterGate( $ );
			ng.init();
		}
	);
})( jQuery );
