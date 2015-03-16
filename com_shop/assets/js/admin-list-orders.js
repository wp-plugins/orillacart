jQuery(document).ready(function () {

    jQuery("select.change_order_status").bind('change', function (event) {

        var status = this.value;
        var id = parseInt(this.id.replace('order_', ''));

        jQuery.ajax({
            type: 'get',
            url: ajaxurl + '?action=ajax-call-admin&component=shop&con=orders&task=update_status&oid=' + id + '&status=' + status,
            success: function (data, text) {
            },
            error: function (request, status, error) {
                throw(request.responseText);

            }
        });
    });

});
