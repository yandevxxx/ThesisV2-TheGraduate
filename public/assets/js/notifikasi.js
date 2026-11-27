
$(document).ready(function () {
    const notifContainer = $('#navbar-notif-list');

    if (!notifContainer.length) {
        return;
    }

    const fetchUrl = notifContainer.data('fetch-url');
    const markReadUrl = notifContainer.data('mark-read-url');
    const allNotifUrl = notifContainer.data('all-notif-url');

    function refreshNotif() {
        $.ajax({
            url: fetchUrl,
            type: 'GET',
            dataType: 'JSON',
            success: function (response) {
                if (response.unread > 0) {
                    $('#navbar-notif-count')
                        .text(response.unread)
                        .removeClass('notif-count-hidden');
                } else {
                    $('#navbar-notif-count')
                        .addClass('notif-count-hidden');
                }

                const container = $('#navbar-notif-list');
                container.empty();

                const header = $('<div>').addClass('notif-header');
                header.append(
                    $('<span>')
                        .addClass('font-weight-bold')
                        .text('Notifications')
                );
                header.append(
                    $('<button>')
                        .attr('type', 'button')
                        .attr('id', 'btn-mark-all')
                        .addClass('text-success small font-weight-bold btn btn-link p-0')
                        .text('Mark all read')
                );

                container.append(header);

                const scrollArea = $('<div>').addClass('notif-scroll-area');

                if (response.list && response.list.length > 0) {
                    response.list.forEach(function (item) {
                        const statusClass =
                            item.is_read_admin == 1
                                ? 'notif-read'
                                : 'notif-unread';

                        const link = $('<a>')
                            .addClass('notif-item-link ' + statusClass);

                        try {
                            const target = new URL(
                                item.link,
                                window.location.origin
                            );

                            if (
                                target.origin !== window.location.origin ||
                                !['http:', 'https:'].includes(target.protocol)
                            ) {
                                return;
                            }

                            link.attr('href', target.href);
                        } catch (error) {
                            return;
                        }

                        link.append(
                            $('<div>')
                                .addClass('text-dark small mb-1')
                                .text(item.pesan)
                        );

                        link.append(
                            $('<div>')
                                .addClass('small text-gray-500')
                                .text('Klik untuk detail')
                        );

                        scrollArea.append(link);
                    });
                } else {
                    scrollArea.append(
                        $('<div>')
                            .addClass('p-4 text-center small text-gray-500')
                            .text('Belum ada pengajuan baru')
                    );
                }

                container.append(scrollArea);

                container.append(
                    $('<a>')
                        .addClass('notif-footer-link')
                        .attr('href', allNotifUrl)
                        .text('Lihat Semua Notifikasi')
                );
            },
            error: function () {
                console.log('Error fetching notifications');
            }
        });
    }

    refreshNotif();
    setInterval(refreshNotif, 10000);

    $(document).on('click', '#btn-mark-all', function (event) {
        event.preventDefault();

        $.get(markReadUrl, function () {
            refreshNotif();
        });
    });
});
