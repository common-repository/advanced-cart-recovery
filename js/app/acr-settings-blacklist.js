jQuery( document ).ready( function ( $ ) {

    /*
     |---------------------------------------------------------------------------------------------------------------
     | Variable Declarations
     |---------------------------------------------------------------------------------------------------------------
     */
    var $blacklist_controls = $( ".blacklist-controls" ),
        $button_controls = $( ".button-controls" ),
        $acr_blacklist_emails_table = $( "#acr-blacklist-emails-table" ),
        errorMessageDuration = "10000",
        successMessageDuration = "5000";

    /*
     |---------------------------------------------------------------------------------------------------------------
     | Helper Functions
     |---------------------------------------------------------------------------------------------------------------
     */
    function removeTableNoItemsPlaceholder ( $table ) {

        $table.find( "tbody" ).find( ".no-items" ).remove();

    }

    function resetTableRowStyling () {

        $acr_blacklist_emails_table
            .find( "tbody" )
            .find( "tr" )
            .each( function( index ) {

                index++; // we do this coz index is zero base

                if ( index % 2 == 0 ) {
                    // even
                    $( this )
                        .removeClass( "odd" )
                        .removeClass( "alternate" )
                        .addClass( "even" );
                } else {
                    // odd
                    $( this )
                        .removeClass( "even" )
                        .addClass( "odd" )
                        .addClass( "alternate" );
                }
            });
    }

    function resetFields () {

        $blacklist_controls.find( "#acr_email_field" ).val( "" );
        $blacklist_controls.find( "#acr_customer_field" ).val('').trigger('change');

    }

    /*
     |---------------------------------------------------------------------------------------------------------------
     | Events
     |---------------------------------------------------------------------------------------------------------------
     */
    $button_controls.find( "#acr-add-email" ).click( function () {

        var $this = $( this ),
            $errFields = [];

        $button_controls.addClass( "processing" );
        $this.attr( "disabled", "disabled" );

        var unsubscribe_type = $blacklist_controls.find( 'input[name="unsubscribe_type_field"]:checked' ).val(),
            customerField = $blacklist_controls.find( '#acr_customer_field' ),
            matchedEmails = [],
            email_field = '',
            attributes = [],
            options = [],
            reason = "manual";

        switch ( unsubscribe_type ) {

            case 'customer':
                var selectedCustomer = customerField.prop('type') == 'select-one' ? customerField.find( 'option:selected' ).text() : $blacklist_controls.find('#s2id_acr_customer_field .select2-chosen').text();
                matchedEmails = selectedCustomer ? selectedCustomer.match(/([a-zA-Z0-9+._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9._-]+)/gi) : '';
                email_field = $.trim( matchedEmails[ matchedEmails.length - 1 ] );
                break;
            case 'email':
                email_field = $.trim( $blacklist_controls.find( "#acr_email_field" ).val() );
                break;
        }

        if ( email_field == "" )
            $errFields.push( acr_blacklist_control_vars.email_empty );

        if ( $errFields.length > 0 ) {

            var errFieldsStr = "";
            for ( var i = 0 ; i < $errFields.length ; i++ ) {

                if ( errFieldsStr != "" )
                    errFieldsStr += ", ";

                errFieldsStr += $errFields[ i ];

            }

            toastr.error( errFieldsStr, acr_blacklist_control_vars.empty_fields_error_message, { "closeButton" : true, "showDuration" : errorMessageDuration } );

            $button_controls.removeClass( "processing" );
            $this.removeAttr( "disabled" );

            return false;

        }

        if( email_field != "" && acrActions.acrIsValidEmail( email_field ) == false ){

            toastr.error( errFieldsStr, acr_blacklist_control_vars.error_email_format, { "closeButton" : true, "showDuration" : errorMessageDuration } );

            $button_controls.removeClass( "processing" );
            $this.removeAttr( "disabled" );

            return false;

        }

        acrBackEndAjaxServices.acrAddEmailToBlacklist( email_field, reason )
            .done( function ( data , textStatus , jqXHR ) {

                if ( data.status == "success" ) {

                    toastr.success( "", acr_blacklist_control_vars.success_save_message, { "closeButton" : true, "showDuration" : successMessageDuration } );

                    removeTableNoItemsPlaceholder( $acr_blacklist_emails_table );

                    var tr_class = "";

                    if( $acr_blacklist_emails_table.find( "tr" ).length % 2 == 0 )
                        tr_class = "odd alternate";
                    else
                        tr_class = "even";

                    $acr_blacklist_emails_table.find( "tbody" )
                        .append( '<tr class="'+tr_class+' edited">' +
                                    '<td class="meta hidden"></td>' +
                                    '<td class="acr_row_email">' + data.email + '</td>' +
                                    '<td class="acr_row_date">' + data.date + '</td>' +
                                    '<td class="acr_row_reason">' + data.reason + '</td>' +
                                    '<td class="controls">' +
                                        '<a class="delete dashicons dashicons-no"></a>' +
                                    '</td>' +
                                '</tr>' );

                    resetFields();

                    setTimeout( function(){
                        $acr_blacklist_emails_table
                            .find( "tr.edited" )
                            .removeClass( "edited" );
                    }, 2000 );

                } else {

                    toastr.error( "", data.msg, { "closeButton" : true, "showDuration" : errorMessageDuration } );

                }
            })
            .fail( function ( jqXHR, textStatus, errorThrown ) {

                toastr.error( jqXHR.responseText, acr_blacklist_control_vars.failed_save_message, { "closeButton" : true, "showDuration" : errorMessageDuration } );

                console.log( acr_blacklist_control_vars.failed_save_message );
                console.log( jqXHR );
                console.log( "----------" );

            })
            .always( function () {

                $button_controls.removeClass( "processing" );
                $this.removeAttr( "disabled" );

            });

    });


    $acr_blacklist_emails_table.on( "click", ".delete", function () {

        var $this = $( this ),
            $current_tr = $this.closest( "tr" );

        if ( confirm( acr_blacklist_control_vars.confirm_box_message ) ) {

            var email = $.trim( $current_tr.find( ".acr_row_email" ).text() );

            $current_tr.addClass( 'edited' );

            $current_tr.find( ".delete" ).removeClass( "dashicons-no" ).addClass( "spinner" ).css( { "margin" : "0px", "visibility" : "visible", "float" : "right" } );

            acrBackEndAjaxServices.acrDeleteEmailFromBlacklist( email )
                .done( function ( data , textStatus , jqXHR ) {

                    if ( data.status == "success" ) {

                        $current_tr.fadeOut( "fast", function(){

                            $current_tr.remove();

                            resetTableRowStyling();

                            if ( $acr_blacklist_emails_table.find( "tbody" ).find( "tr" ).length <= 0 ) {

                                $acr_blacklist_emails_table
                                    .find( "tbody" )
                                    .html(  '<tr class="no-items">' +
                                    '<td class="colspanchange" colspan="7">' + acr_blacklist_control_vars.no_emails_message + '</td>' +
                                    '</tr>');

                            }

                        });

                        toastr.success( "", acr_blacklist_control_vars.success_delete_message, { "closeButton" : true, "showDuration" : successMessageDuration } );

                    } else {

                        toastr.error( data.msg, acr_blacklist_control_vars.failed_delete_message, { "closeButton" : true, "showDuration" : errorMessageDuration } );

                        console.log( acr_blacklist_control_vars.failed_delete_message );
                        console.log( data );
                        console.log( "----------" );

                    }

                })
                .fail( function ( jqXHR, textStatus, errorThrown ) {

                    toastr.error( jqXHR.responseText, acr_blacklist_control_vars.failed_delete_message, { "closeButton" : true, "showDuration" : errorMessageDuration } );

                    console.log( acr_blacklist_control_vars.failed_delete_message );
                    console.log( jqXHR );
                    console.log( "----------" );

                })
                .always( function () {

                    $current_tr.find( ".delete" ).removeClass( "spinner" ).addClass( "dashicons-no" );

                });
        }
    });

    $blacklist_controls.on( 'change' , 'input[name="unsubscribe_type_field"]' , function() {

        var type         = $(this).val()
            select2Field = $( '.select2-field' );
            manualField  = $( '.manual-email-field' );

        switch ( type ) {
            case 'customer':
                select2Field.show();
                manualField.hide();
                break;
            case 'email':
                select2Field.hide();
                manualField.show();
                break;
        }
    });

});
