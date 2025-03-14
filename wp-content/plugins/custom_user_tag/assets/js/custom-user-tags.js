jQuery(document).ready(function ($) {

    let selectedTag = $("#filter_tag").val();

    $('#filter_tag').select2({
        ajax: {
            url: custom_user_tag_obj.ajaxURL,
            dataType: "json",
            delay: 500,
            data: (param) => {
                return {
                    action: "fetch_user_tags",
                    q: param.term
                }
            },
            processResults: (data) => {
                return {
                    results: data
                };
            },
            cache: true
        },
        placeholder: 'Filter by User Tags…',
        allowClear: true,
        minimumInputLength: 1,
        dropdownAutoWidth: true
    });


    // If a tag was previously selected, set it as the default selected option
    if (selectedTag) {
        $.ajax({
            url: custom_user_tag_obj.ajax_url,
            dataType: "json",
            data: {
                action: "fetch_user_tags",
                q: selectedTag
            },
            success: function (data) {
                if (data.length > 0) {
                    let selectedOption = new Option(data[0].text, data[0].id, true, true);
                    $("#filter_tag").append(selectedOption).trigger("change");
                }
            }
        });
    }
});