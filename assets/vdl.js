
jQuery(document).ready(function($) {
    function clickWhenElementExists(selector, callback) {
        // Immediate check
        if ($(selector).length) {
            callback();
            return;
        }
        
        // Observer for future changes
        const observer = new MutationObserver(() => {
            if ($(selector).length) {
                callback();
                observer.disconnect();
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    if ( wp_ajax.option ) {
        $('#vdl-enable-shortcut').attr('checked',true);
    }
    if ( wp_ajax.show_settings == 'yes' ) {
        if (typeof wp_ajax !== 'undefined' && wp_ajax.show_settings === 'yes') {
            clickWhenElementExists('#contextual-help-link', () => {
                setTimeout(() => {
                    $('#contextual-help-link').trigger('click');
                }, 2);
            });
        }
    }
    $('#contextual-help-link').text('Settings');
    $('#vdl-enable-shortcut').change(function() {
        var isChecked = $(this).is(':checked') ? 1 : 0;
        console.log('ischecked', isChecked);
        var responseContainer = $('#vdl-ajax-response');
        responseContainer.html('<p class="vdl-loading">Saving...</p>');
        
        $.ajax({
            url:  wp_ajax.url,
            type: 'POST',
            data: {
                action: 'settings_save',
                shortcut_enabled: isChecked,
                vdl_nonce: wp_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    responseContainer.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                } else {
                    responseContainer.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
                // Fade out after 3 seconds
                setTimeout(function() {
                    responseContainer.fadeOut('slow', function() {
                        // This executes after fade-out completes
                        $(this).html('').show();
                    });
                }, 3000);
            },
            error: function() {
                responseContainer.html('<div class="notice notice-error"><p>Error saving settings.</p></div>');
            }
        });
    });
});