$(function () {

    $('#sidebarToggle').on('click', function () {
        $('#appSidebar').toggleClass('show');
    });

    $('[data-toggle="nav-parent"]').on('click', function (e) {
        e.preventDefault();
        var target = $(this).attr('data-target');
        var $target = $(target);
        $('.collapse.show').not($target).collapse('hide');
        $target.collapse('toggle');
    });

    $(document).on('click', '[data-edit]', function () {
        var data = $(this).data('edit');
        var $modal = $('#recordModal');
        $.each(data, function (key, value) {
            var $el = $modal.find('[name="' + key + '"]');
            if ($el.length) {
                if ($el.is('select')) {
                    $el.val(String(value));
                } else {
                    $el.val(value);
                }
            }
        });
        $modal.find('#recordId').val(data.id || '');
        $modal.find('.modal-title').text('Edit Record');
    });

    $(document).on('click', '[data-add]', function () {
        var $modal = $('#recordModal');
        $modal.find('form')[0].reset();
        $modal.find('#recordId').val('');
        $modal.find('.modal-title').text($(this).data('add'));
    });

    $(document).on('submit', '[data-confirm]', function () {
        return confirm($(this).data('confirm'));
    });

    $('.table-search').on('keyup', function () {
        var q = $(this).val().toLowerCase();
        var table = $(this).data('table');
        $(table + ' tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) > -1);
        });
    });

    window.setTimeout(function () {
        $('.alert-dismissible').fadeOut(400, function () { $(this).remove(); });
    }, 5000);

    $('[data-mask-money]').on('input', function () {
        var v = $(this).val().replace(/[^0-9.]/g, '');
        $(this).val(v);
    });
});
