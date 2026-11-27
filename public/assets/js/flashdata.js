
$(document).ready(function () {
    const flashdata = $('#flashdata');

    if (flashdata.length && flashdata.data('open') === true) {
        const icon = $('#icon').text().trim();
        const title = $('#title').text().trim();
        const text = $('#text').text().trim();

        Swal.fire({
            icon: icon || 'info',
            title: title,
            text: text,
            confirmButtonText: 'OK'
        });
    }
});
