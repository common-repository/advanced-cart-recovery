var acrActions = function(){

    var acrIsValidEmail = function( email ){

            var pattern = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return pattern.test(email);

        },
        acrResetTableRowStyling = function( $schedulesTable ) {

            $schedulesTable
                .find( "tbody" )
                .find( "tr" )
                .each( function( index ) {
                    index++;
                    if ( index % 2 == 0 ) {
                        jQuery( this )
                            .removeClass( "odd" )
                            .removeClass( "alternate" )
                            .addClass( "even" );
                    } else {
                        jQuery( this )
                            .removeClass( "even" )
                            .addClass( "odd" )
                            .addClass( "alternate" );
                    }
                });

        },
        acrSortEmailSchedules = function( $rows, $schedulesTable ){

            $rows.sort( function( a, b ) {
                var A = parseInt( jQuery( a ).find( ".acr-days-after-abandoned" ).text() );
                var B = parseInt( jQuery( b ).find( ".acr-days-after-abandoned" ).text() );

                if( A < B ) {
                    return -1;
                }

                if( A > B ) {
                    return 1;
                }

                return 0;

            });

            jQuery( $rows ).each( function( index, row ) {
                $schedulesTable.children( 'tbody' ).append( row );
            });

        },
        acrDelegateFieldEntry = function( $formControls, key, scheduled_data ){

            $formControls.find( "#acr_email_subject_field" ).val( scheduled_data.subject );
            
            if( scheduled_data.wrap.toLowerCase() === "yes" )
                $formControls.find( "#acr_email_wrap_wc_header_footer_field" ).prop( "checked", true );
            else
                $formControls.find( "#acr_email_wrap_wc_header_footer_field" ).prop( "checked", false );

            $formControls.find( "#acr_email_heading_text" ).val( scheduled_data.heading_text );
            $formControls.find( "#acr_email_days_after_order_abandoned_field" ).val( scheduled_data.days_after_abandoned );
            
            // Append data into the Visual and Text editor.
            if( $formControls.find( ".wp-editor-wrap" ).hasClass( "tmce-active" ) ){
                $formControls.find( "#acr_email_content_field_ifr" ).contents().find( "#tinymce" ).html( scheduled_data.content );
            }else if( $formControls.find( ".wp-editor-wrap" ).hasClass( "html-active" ) ){
                $formControls.find( "#acr_email_content_field" ).val( scheduled_data.content );
            }

            $formControls.find( "#acr_email_schedule_id_field" ).val( key );

        },
        acrCancelUpdate = function( $formControls, $schedulesTable ){

            $formControls.find( "#acr_email_subject_field" ).val( "" );
            $formControls.find( "#acr_email_wrap_wc_header_footer_field" ).prop( "checked", false );
            $formControls.find( "#acr_email_heading_text" ).val( "" );
            $formControls.find( "#acr_email_days_after_order_abandoned_field" ).val( "" );
            $formControls.find( "#acr_email_content_field_ifr" ).contents().find( "#tinymce" ).html( "" );
            $formControls.find( "#acr_email_schedule_id_field" ).val( "" );
        
            $formControls.slideUp( 200 );

            setTimeout( function(){
                $schedulesTable
                    .find( "tr.edited" )
                    .removeClass( "edited" );
            },  1000 );

        },
        acrWrapCheck = function( $formControls ){

            if( $formControls.find( "#acr_email_wrap_wc_header_footer_field" ).attr( "checked" ) ){
                $formControls.find( "input#acr_email_heading_text" ).closest( "tr" ).show();
            }else{
                $formControls.find( "input#acr_email_heading_text" ).closest( "tr" ).hide();
            }

        },
        acrValidateEmailContent = function( $formControls ){

            var email_content_field;

            if( $formControls.find( ".wp-editor-wrap" ).hasClass( "tmce-active" ) ){
                var $contents = $formControls.find( "#acr_email_content_field_ifr" ).contents().find( "#tinymce" );
                if( $contents.find( 'br' ).attr( 'data-mce-bogus' ) == '1' ){
                    email_content_field = '';
                }else{   
                    email_content_field = $contents.html();
                }
            }else if( $formControls.find( ".wp-editor-wrap" ).hasClass( "html-active" ) ){
                email_content_field = $formControls.find( "#acr_email_content_field" ).val();
            }

            return email_content_field;
        };

    return {
        acrIsValidEmail 			:   acrIsValidEmail,
        acrResetTableRowStyling     :   acrResetTableRowStyling,
        acrSortEmailSchedules       : 	acrSortEmailSchedules,
        acrDelegateFieldEntry       :   acrDelegateFieldEntry,
        acrCancelUpdate             :   acrCancelUpdate,
        acrWrapCheck                :   acrWrapCheck,
        acrValidateEmailContent     :   acrValidateEmailContent
    };

}();