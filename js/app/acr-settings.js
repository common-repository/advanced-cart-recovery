jQuery(document).ready(function($){

    // Use Default Email Template Body
    var acrDefaultContent = $('#acr-show-default-template');

    $(this.body).on("click", "#acr-use-default-email-content", function(e){
        e.preventDefault();
        $(acrDefaultContent).slideToggle("fast");
    });

    $("#acr_general_status_considered_abandoned").select2({
	  placeholder: "Select Status",
	  allowClear: true
	});

    $("#acr_general_status_considered_completed").select2({
      placeholder: "Select Status",
      allowClear: true
    });
});