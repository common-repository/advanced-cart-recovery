/**
 * A function implementing the revealing module pattern to house all ajax request. It implements the ajax promise methodology
 * @return {Ajax Promise} promise it returns a promise, I promise that #lamejoke
 *
 * Info:
 * ajaxurl points to admin ajax url for ajax call purposes. Added by wp when script is wp enqueued
 */
var acrBackEndAjaxServices = function(){

    var acrAddEmailToBlacklist = function( email, reason ){

            return jQuery.ajax({
                url         :   ajaxurl,
                type        :   "POST",
                data        :   { action : "acrAddEmailToBlacklist" , email : email, reason : reason },
                dataType    :   "json"
            });

        },
        acrDeleteEmailFromBlacklist = function( email ){

            return jQuery.ajax({
                url         :   ajaxurl,
                type        :   "POST",
                data        :   { action : "acrDeleteEmailFromBlacklist" , email : email },
                dataType    :   "json"
            });

        },
        acrViewEmailSchedule = function( key ){

            return jQuery.ajax({
                url         :   ajaxurl,
                type        :   "POST",
                data        :   { action : "acrViewEmailSchedule" , key : key },
                dataType    :   "json"
            });

        },
        acrUpdateEmailSchedule = function( key, email_fields ){

            return jQuery.ajax({
                url         :   ajaxurl,
                type        :   "POST",
                data        :   { action : "acrUpdateEmailSchedule" , key : key, email_fields : email_fields },
                dataType    :   "json"
            });

        };

    return {
        acrAddEmailToBlacklist          :   acrAddEmailToBlacklist,
        acrDeleteEmailFromBlacklist     :   acrDeleteEmailFromBlacklist,
        acrViewEmailSchedule            :   acrViewEmailSchedule,
        acrUpdateEmailSchedule          :   acrUpdateEmailSchedule
    }

}();