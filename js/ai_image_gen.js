(function ($, Drupal) {
  Drupal.behaviors.aiImageGenerator = {
    attach: function (context) {
      console.log('Attaching Image Generator behavior.');

      // Only run the behavior when #media-image-add-form is in the context
      $(context).find('#media-image-add-form').each(function () {
        // Log context to confirm it's being scoped to the form
        console.log('Found #media-image-add-form in context', context);

        const generateButton = $(this).find('#edit-generate');
        console.log('Generate button found:', generateButton.length);

        // Log the button element before the event is bound to confirm it’s correctly selected
        console.log('Button element:', generateButton);

        console.log(generateButton.prop('disabled')); // Should return false if the button is enabled


        // Only attach the event listener if the button exists
        if (generateButton.length) {
          generateButton.on('click', function (e) {
            e.preventDefault();

            console.log('#edit-generate clicked.');

            const prompt = $('#edit-prompt').val();
            const baseUrl = drupalSettings.path.baseUrl || '/';
            console.log('Base URL:', baseUrl);

            if (!prompt) {
              alert('Please enter a prompt.');
              return;
            }

            console.log('Request URL:', `${baseUrl}api/ai-image-gen/generate/${encodeURIComponent(prompt)}`);

            // Add throbber before AJAX call
            $('#edit-image-preview').html('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div><div class="message">' + Drupal.t('Generating image...') + '</div></div>');


            $.ajax({
              url: `${baseUrl}api/ai-image-gen/generate/${encodeURIComponent(prompt)}`,
              method: 'GET',
              success: function (response) {
                console.log('Response received:', response);
                if (response && response.file_url) {
                  $('#edit-image-preview').html(`<img src="${response.file_url}" alt="${prompt}">`);
                  $('input[name="field_media_image[0][fids]"]').val(response.media_id);
                } else {
                  console.warn('Unexpected response format:', response);
                }
              },
              error: function (xhr, status, error) {
                console.error(`Error generating image: ${status} - ${error}`);
                console.error('Server response:', xhr.responseText);
              },
            });
          });
        }
        else {
          console.error('Generate button not found');
        }
      });
    },
  };
})(jQuery, Drupal);
