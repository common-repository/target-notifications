(function ($) {

    $(document).on('click', '.automatic-radio', function (e) {
        if (!$(e.target).is("label")) {

            if ($(this).children().val() == "0") {
                $('input[name="target_email_schedule_number"]').parent().parent().addClass('disabled');
                $('div[name="target_body"]').show();
                $('div[name="target_body_bulk"]').hide();
            }
            else {
                $('div[name="target_body"]').hide();
                $('div[name="target_body_bulk"]').show();
                $('input[name="target_email_schedule_number"]').parent().parent().removeClass('disabled');
            }
        }
    });
    $(document).on('click', '.tag-checkbox', function (e) {
        if (!$(e.target).is("label")) {
            checkboxVal = $(this).children().val();
            selector = '.tag_description.' + checkboxVal;
            if ($(this).children().is(':checked')) {
                $(selector).show();
            }
            else {
                $(selector).hide();
            }
        }
    });
    $(document).on('click', '.cat-checkbox', function (e) {
        if (!$(e.target).is("label")) {
            checkboxVal = $(this).children().val();
            selector = '.cat_description.' + checkboxVal;
            if ($(this).children().is(':checked')) {
                $(selector).show();
            }
            else {
                $(selector).hide();
            }
        }
    });
    // $(document).on('click', '.mailchimp_next', function (e) {
    //     $('.mailchimp_first').hide();
    //     $('.mailchimp_last').show();

    // });
})(jQuery);

