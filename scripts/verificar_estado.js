$(document).ready(function () {
    function checkStatus() {
        var id = new URLSearchParams(window.location.search).get('id');
        if (!id) return;

        $.ajax({
            url: '../../check_status.php', // Adjust path based on cargando.php location
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    if (response.new_location) {
                        window.location.href = response.new_location;
                    }
                }
            },
            error: function () {
                console.log('Error checking status');
            }
        });
    }

    // Poll every 2 seconds
    setInterval(checkStatus, 2000);
});
