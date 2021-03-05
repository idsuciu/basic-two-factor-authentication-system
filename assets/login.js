var $container = $('.js-code');

$container.on('click', function(e) {
    e.preventDefault();

    $.ajax({
        url: '/second-step',
        method: 'POST',
        data: {
            code: $('.code-input').val()
        }
    }).then(function(data) {
        if (data.error) {
            $('#error').removeClass("d-none");
            $('.alert').text(data.message);
        } else {
            location.href = data.url;
        }
    });
});