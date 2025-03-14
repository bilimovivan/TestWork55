jQuery(document).ready(function($) {
    $('#city-search-btn').on('click', function(e) {
        e.preventDefault();
        var searchTerm = $('#city-search').val();
        $.ajax({
            url: citiesAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'search_cities',
                search: searchTerm,
                nonce: citiesAjax.nonce
            },
            success: function(response) {
                $('#cities-table-result').html(response);
            },
            error: function() {
                alert('Error occurred while searching.');
            }
        });
    });
});
