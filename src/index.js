import './css/shortcode.scss';

function NewsletterGate($){
    this.init = function init() {
        $(document).on( 'submit', '.newslettergate-form', function (e){
            e.preventDefault();

            var $this = $(this),
                container = $this.parents('.newslettergate' );

            container.addClass('newslettergate-loading');
            $.ajax({
                method: 'POST',
                url: newslettergate.ajax,
                data: {
                    nonce: newslettergate.nonce,
                    data: $(this).serialize(),
                    action: $(this).find('[name=ng_action]').val()
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
                    container.removeClass('newslettergate-loading');
                }
            })

        });
    }

    return this;
}

(function ($){
    $(function(){
        var ng = new NewsletterGate($);
        ng.init();
    });
})(jQuery);
