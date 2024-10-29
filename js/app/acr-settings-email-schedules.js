jQuery( document ).ready( function ( $ ) {

    /*
     |---------------------------------------------------------------------------------------------------------------
     | Variable Declarations
     |---------------------------------------------------------------------------------------------------------------
     */
    var $acr_forms_controls = $( ".acr-email-schedules-controls" ),
        $acr_button_controls = $( ".acr-button-controls" ),
        $acr_email_schedules_table = $( "#acr-email-schedules-table" ),
        $acr_email_schedules_table_rows,
        errorMessageDuration = "10000",
        successMessageDuration = "5000";

    /*
     |---------------------------------------------------------------------------------------------------------------
     | Events
     |---------------------------------------------------------------------------------------------------------------
     */

    // View Scheduled Email
    $acr_email_schedules_table.delegate( ".view" , "click", function ( event ) {

        var $this = $( this ),
            $current_tr = $this.closest( "tr" ),
            $viewDialogBox = $( "#acr-view-data" ),
            $acrViewData = $( "#acr-view-data" ),
            key = $this.siblings( ".key" ).val();

            $current_tr.find( ".view" ).removeClass( "dashicons-search" ).addClass( "spinner" ).css({ "margin" : "0px 15px 0px 0px", "visibility" : "visible" });

            $this
                .attr( "disabled", "disabled" )
                    .siblings( ".spinner" )
                        .css({
                                display : "inline-block",
                                visibility : "visible"
                            });

            acrBackEndAjaxServices.acrViewEmailSchedule( key )
                .done( function ( data, textStatus, jqXHR ) {

                    $current_tr.find( ".view" ).removeClass( "spinner" ).addClass( "dashicons-search" ).css({ "visibility" : "visible" });

                    if ( data.status == "success" ) {

                        var days = data.scheduled_data.days_after_abandoned;
                        if( days > 1 ){
                            days = days + " Days";
                        }else{
                            days = days + " Day";
                        }

                        $acrViewData.find( ".acr_email_subject_value" ).html( data.scheduled_data.subject );
                        $acrViewData.find( ".acr_email_wrap_wc_header_footer_value" ).html( data.scheduled_data.wrap );
                        $acrViewData.find( ".acr_email_heading_text_value" ).html( data.scheduled_data.heading_text );
                        $acrViewData.find( ".acr_email_days_after_order_abandoned_value" ).html( days );
                        $acrViewData.find( ".acr_email_content_value" ).html( data.scheduled_data.content );

                        $viewDialogBox.dialog({
                            dialogClass: "email-schedules",
                            modal: true,
                            zIndex: 10000,
                            autoOpen: true,
                            width: "auto",
                            resizable: false,
                            close: function ( event, ui ) {

                                $( this ).dialog("close");
                                $acrViewData.find( ".acr_email_subject_value" ).html( "" );
                                $acrViewData.find( ".acr_email_wrap_wc_header_footer_value" ).html( "" );
                                $acrViewData.find( ".acr_email_days_after_order_abandoned_value" ).html( "" );
                                $acrViewData.find( ".acr_email_content_value" ).html( "" );

                            }
                        });

                    } else {

                        console.log( acr_email_schedule_control_vars.failed_view );
                        console.log( data );
                        console.log( "----------" );

                    }

                })
                .fail( function ( jqXHR, textStatus, errorThrown ) {

                    console.log( acr_email_schedule_control_vars.failed_view );
                    console.log( jqXHR );
                    console.log( "----------" );

                })
                .always( function () {

                    $this
                        .removeAttr( "disabled" )
                            .siblings( ".spinner" )
                                .css( {
                                    display : "none",
                                    visibility : "hidden"
                                });

                });
    });

    // Populate Scheduled Email
    $acr_email_schedules_table.delegate( ".edit", "click", function () {

        acrActions.acrCancelUpdate( $acr_forms_controls, $acr_email_schedules_table );

        var $this = $( this ),
            $current_tr = $this.closest( "tr" ),
            editDialogBox = $( "#acr-email-schedules-controls" ),
            $showHideForm = $( "#acr-show-form" ),
            acrScheduleForm = $( "#acr-email-schedules-controls" ),
            key = $this.siblings( ".key" ).val();

            $current_tr.siblings( "tr" ).removeClass( "edited" );
            $current_tr.addClass( "edited" );

            $current_tr.find( ".edit" ).removeClass( "dashicons-edit" ).addClass( "spinner" ).css({ "margin" : "0px 15px 0px 0px", "visibility" : "visible" });

            $this
                .attr( "disabled" , "disabled" )
                    .siblings( ".spinner" )
                        .css( {
                                display : "inline-block",
                                visibility : "visible"
                            });

            $('#acr-email-schedules-table tr').removeClass( 'current-editing' );
            $this.closest('tr').addClass( 'current-editing' );

            acrBackEndAjaxServices.acrViewEmailSchedule( key )
                .done( function ( data, textStatus, jqXHR ) {

                    $current_tr.find( ".edit" ).removeClass( "spinner" ).addClass( "dashicons-edit" ).css({ "visibility" : "visible"});

                    if ( data.status == "success" ) {

                        // Show form
                        $( acrScheduleForm ).slideDown( 200 );
                        $acr_button_controls.find( "#acr-update-email-schedule" ).show();
                        $acr_button_controls.find( "#acr-cancel-email-schedule" ).show();
                        $acr_button_controls.find( "#acr-add-email-schedule" ).hide();

                        acrActions.acrDelegateFieldEntry( $acr_forms_controls, key, data.scheduled_data );
                        acrActions.acrWrapCheck( $acr_forms_controls );

                    } else {

                        console.log( acr_email_schedule_control_vars.failed_view );
                        console.log( data );
                        console.log( "----------" );

                    }

                })
                .fail( function ( jqXHR, textStatus, errorThrown ) {

                    console.log( acr_email_schedule_control_vars.failed_view );
                    console.log( jqXHR );
                    console.log( "----------" );

                })
                .always( function () {

                    $this
                        .removeAttr( "disabled" )
                            .siblings( ".spinner" )
                                .css({
                                    display : "none",
                                    visibility : "hidden"
                                });

                });
    });

    // Cancel Update
    $acr_button_controls.find( "#acr-cancel-email-schedule" ).click( function () {

        acrActions.acrCancelUpdate( $acr_forms_controls, $acr_email_schedules_table );
        $('#acr-email-schedules-table tr').removeClass( 'current-editing' );

    });

    // Update Scheduled Email
    $acr_button_controls.find( "#acr-update-email-schedule" ).click( function () {

        var $this = $( this ),
            $errFields = [],
            email_subject_field = $.trim( $acr_forms_controls.find( "#acr_email_subject_field" ).val() ),
            email_wrap_field = $.trim( $acr_forms_controls.find( "#acr_email_wrap_wc_header_footer_field:checked" ).val() ),
            heading_text_field = $.trim( $acr_forms_controls.find( "#acr_email_heading_text" ).val() ),
            email_days_after_abandoned_field = $.trim( $acr_forms_controls.find( "#acr_email_days_after_order_abandoned_field" ).val() ),
            email_content_field = acrActions.acrValidateEmailContent( $acr_forms_controls ),
            key = $.trim( $acr_forms_controls.find( "#acr_email_schedule_id_field" ).val() ),
            days = $( "#acr-email-schedules-table tr td:nth-child(3)" );

        $this
            .attr( "disabled" , "disabled" )
                .siblings( ".spinner" )
                    .css({
                        display : "inline-block",
                        visibility : "visible"
                    });

        if ( email_subject_field == "" )
            $errFields.push( acr_email_schedule_control_vars.subject_empty );

        if ( email_days_after_abandoned_field == "" )
            $errFields.push( acr_email_schedule_control_vars.days_empty );

        if ( email_days_after_abandoned_field != "" && email_days_after_abandoned_field <= 0 )
            $errFields.push( acr_email_schedule_control_vars.days_positive_only );

        $( days ).each( function(){
            var d = parseInt( $( this ).text() ),
                emailKey = $( this ).siblings( ".controls" ).find( ".key" ).val();

            if( emailKey !== key && d == email_days_after_abandoned_field ){
                $errFields.push( acr_email_schedule_control_vars.days_duplicate_values );
                return false;
            }
        });


        if( email_wrap_field )
            email_wrap_field = "yes";
        else
            email_wrap_field = "no";

        if( email_wrap_field == "yes" && heading_text_field == "" ){
            $errFields.push( acr_email_schedule_control_vars.heading_text_empty );
        }

        if ( email_content_field == "" )
            $errFields.push( acr_email_schedule_control_vars.content_empty );

        if ( $errFields.length > 0 ) {

            var errFieldsStr = "";
            for ( var i = 0 ; i < $errFields.length ; i++ ) {

                if ( errFieldsStr != "" )
                    errFieldsStr += '<br/>';

                errFieldsStr += $errFields[ i ];

            }

            toastr.error( errFieldsStr, acr_email_schedule_control_vars.empty_fields_error_message, { "closeButton" : true, "showDuration" : errorMessageDuration });

            $acr_button_controls.removeClass( "processing" );

            $this
                .removeAttr( "disabled" )
                    .siblings( ".spinner" )
                        .css({
                            display : "none",
                            visibility : "hidden"
                        });

            return false;

        }else{

            email_fields = {
                                subject : email_subject_field,
                                wrap : email_wrap_field,
                                heading_text : heading_text_field,
                                days_after_abandoned : email_days_after_abandoned_field,
                                content : email_content_field
                            };
        }

        acrBackEndAjaxServices.acrUpdateEmailSchedule( key, email_fields )
            .done( function ( data, textStatus, jqXHR ) {

                if ( data.status == "success" ) {

                    var daysAbandoned = data.email_fields.days_after_abandoned;

                    if( daysAbandoned > 1 ){
                        daysAbandoned = daysAbandoned + " Days";
                    }else{
                        daysAbandoned = daysAbandoned + " Day";
                    }

                    $acr_email_schedules_table.find( ".acr-email-id-" + key ).find( ".acr-subject" ).html( data.email_fields.subject );
                    $acr_email_schedules_table.find( ".acr-email-id-" + key ).find( ".acr-wrap-wc-header-footer" ).html( data.email_fields.wrap );
                    $acr_email_schedules_table.find( ".acr-email-id-" + key ).find( ".acr-days-after-abandoned" ).html( daysAbandoned );
                    $acr_email_schedules_table.find( ".acr-email-id-" + key ).find( ".acr-content" ).html( data.email_fields.content );

                    toastr.success( "" , acr_email_schedule_control_vars.success_edit_message, { "closeButton" : true, "showDuration" : successMessageDuration });

                    // Sort rows by day
                    $acr_email_schedules_table_rows = $( "#acr-email-schedules-table tbody tr" ).get();
                    $( $acr_email_schedules_table_rows ).remove();
                    acrActions.acrSortEmailSchedules( $acr_email_schedules_table_rows, $acr_email_schedules_table );
                    acrActions.acrResetTableRowStyling( $acr_email_schedules_table );

                    // Hide form
                    $acr_forms_controls.slideUp( 200 );

                    setTimeout( function(){
                        $acr_email_schedules_table
                            .find( "tr.edited" )
                            .removeClass( "edited" );
                    }, 1000 );

                } else {

                    toastr.error( data.error_message, acr_email_schedule_control_vars.failed_edit_message, { "closeButton" : true, "showDuration" : errorMessageDuration });

                    console.log( acr_email_schedule_control_vars.failed_edit_message );
                    console.log( data );
                    console.log( "----------" );

                }

            })
            .fail( function ( jqXHR, textStatus, errorThrown ) {

                toastr.error( jqXHR.responseText, acr_email_schedule_control_vars.failed_edit_message, { "closeButton" : true, "showDuration" : errorMessageDuration });

                console.log( acr_email_schedule_control_vars.failed_edit_message );
                console.log( jqXHR );
                console.log( "----------" );

            })
            .always( function () {

                $this
                    .removeAttr( "disabled" )
                        .siblings( ".spinner" )
                            .css({
                                display : "none",
                                visibility : "hidden"
                            });

            });

            $('#acr-email-schedules-table tr').removeClass( 'current-editing' );
            
    });

    // Option to add heading text to the email when wrapping with wc header and footer is enabled
    acrActions.acrWrapCheck( $acr_forms_controls );
    $acr_forms_controls.find( "#acr_email_wrap_wc_header_footer_field" ).off().on( "click", function(){
        if( $( this ).attr( "checked" ) ){
            $acr_forms_controls.find( "input#acr_email_heading_text" ).closest( "tr" ).show();
        }else{
            $acr_forms_controls.find( "input#acr_email_heading_text" ).closest( "tr" ).hide();
        }
    });
});
