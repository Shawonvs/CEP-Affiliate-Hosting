jQuery(document).ready(function ($) {
    $('.nav-tab').on('click', function (e) {
        e.preventDefault();

        const tab = $(this).data('tab');
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('#cep-tab-content').html('<p>Loading...</p>');

        $.post(cepAjax.url, {
            action: 'cep_load_tab_content',
            tab: tab,
            _ajax_nonce: cepAjax.nonce,
        })
            .done(function (response) {
                if (response.success) {
                    $('#cep-tab-content').html(response.data);
                } else {
                    $('#cep-tab-content').html('<p>Error loading content.</p>');
                }
            })
            .fail(function () {
                $('#cep-tab-content').html('<p>Error loading content.</p>');
            });
    });

    // Trigger click on the first tab to load its content
    $('.nav-tab-active').trigger('click');
});
