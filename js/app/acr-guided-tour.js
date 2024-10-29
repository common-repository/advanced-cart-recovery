(function(){
    window.ACR = window.ACR ||{ Admin: {} };
}());

(function($){

    function Tour() {

        if ( !acr_guided_tour_params.screen.elem )
            return;
        this.initPointer();

    }

    Tour.prototype.initPointer = function(){

        var self = this;
        self.$elem = $( acr_guided_tour_params.screen.elem ).pointer({
            content: acr_guided_tour_params.screen.html,
            position: {
                align: acr_guided_tour_params.screen.align,
                edge: acr_guided_tour_params.screen.edge,
            },
            buttons: function( event, t ){
                return self.createButtons( t );
            },
        }).pointer( 'open' );

    };

    Tour.prototype.createButtons = function( t ) {

        this.$buttons = $( '<div></div>', {
            'class': 'acr-tour-buttons'
        });

        this.createCloseButton( t );
        this.createPrevButton( t );
        this.createNextButton( t );

        return this.$buttons;

    };

    Tour.prototype.createCloseButton = function( t ) {

        var $btnClose = $( '<button></button>', {
            'class': 'button button-large',
            'type': 'button'
        }).html( acr_guided_tour_params.texts.btn_close_tour );

        $btnClose.click(function() {
            
            var data = {
                action : acr_guided_tour_params.actions.close_tour,
                nonce  : acr_guided_tour_params.nonces.close_tour,
            };

            $.post( acr_guided_tour_params.urls.ajax, data, function( response ) {
                
                if ( response.success )
                    t.element.pointer( 'close' );
                
            });

        });

        this.$buttons.append($btnClose);

    };

    Tour.prototype.createPrevButton = function( t ) {

        if ( !acr_guided_tour_params.screen.prev )
            return;

        var $btnPrev = $( '<button></button>' , {
            'class': 'button button-large',
            'type': 'button'
        } ).html( acr_guided_tour_params.texts.btn_prev_tour );

        $btnPrev.click( function(){
            window.location.href = acr_guided_tour_params.screen.prev;
        });

        this.$buttons.append($btnPrev);

    };

    Tour.prototype.createNextButton = function( t ) {

        if ( !acr_guided_tour_params.screen.next )
            return;

        // Check if this is the first screen of the tour.
        var text = ( !acr_guided_tour_params.screen.prev ) ? acr_guided_tour_params.texts.btn_start_tour : acr_guided_tour_params.texts.btn_next_tour;

        var $btnStart = $( '<button></button>', {
            'class' : 'button button-large button-primary',
            'type'  : 'button'
        }).html( text );

        $btnStart.click( function() {
            window.location.href = acr_guided_tour_params.screen.next;
        } );

        this.$buttons.append( $btnStart );

    };

    ACR.Admin.Tour = Tour;

    // DOM ready
    $( function() {
        new ACR.Admin.Tour();
    });

}(jQuery));
