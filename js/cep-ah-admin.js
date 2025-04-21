jQuery(document).ready(function($){
    function loadTab(tab) {
        $('#cep-ah-tab-content').html('<div class="cep-ah-loading">Loading...</div>');
        $.post(cepAhAjax.ajax_url, {
            action: 'cep_ah_tab',
            tab: tab,
            _ajax_nonce: cepAhAjax.nonce
        }, function(response) {
            $('#cep-ah-tab-content').html(response);
        });
    }
    $('#cep-ah-tabs').on('click', '.nav-tab', function(e){
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        loadTab($(this).data('tab'));
    });
    // Load default tab
    loadTab('settings');
});
