define(['jquery', 'core/ajax', 'core/templates', 'core/str'], function($, ajax, templates, {getString}) {
    return {
        init: function(userIdParam) {
            userid = parseInt(userIdParam);

                //$('#loading-icon').show(); // Show loading icon
                ajax.call([{
                    methodname: 'block_learningpath_get_learningpath',
                    args: {}
                }])[0].done(function(response) {
                    templates.render('block_learningpath/learningpaths_table', { learningpathsData: response })
                        .done(function(html) {
                            $('#learningpath_table').html(html).show();
                            $('#loading-icon').hide();
                        });
                }).fail(function(ex) {
                    console.error(ex);
                    $('#loading-icon').hide();
                });

             // Click event to load Learning Line Details
            $(document).on('click', '.load-line-detail', function() {
                var lpt_id = $(this).data('id');

                $('#learningpath_table').hide(); // Hide main table
                $('#learninglines_container').show();
                $('#loading-icon').show();

                ajax.call([{
                    methodname: 'block_learningpath_get_detail_line',
                    args: { 
                        lpt_id: lpt_id,
                        u_id: userid // Use global userid
                    }
                }])[0].done(function(response) {
                    templates.render('block_learningpath/learninglines_table', { learninglinesData: response })
                        .done(function(html) {
                            $('#learninglines_table').html(html).show();
                            $('#loading-icon').hide();
                        });
                }).fail(function(ex) {
                    console.error(ex);
                    $('#loading-icon').hide();
                    $('#learninglines_table').html('<p class="text-danger">' + getString('failedtoload', 'block_learningpath') + '</p>');
                });
            });

            // Back Button: Return to Main Table
            $(document).on('click', '#back-button', function() {
                $('#learninglines_container').hide();
                $('#learningpath_table').show();
            });

        }
    };
});
