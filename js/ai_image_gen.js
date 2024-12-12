(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.aiAltImage = {
    trackedImages: {},
    finishedWorking: (that) => {
      // Untrack the file.
      Drupal.behaviors.aiAltImage.trackedImages[$(that).data('file-id')] = false;
      // Remove the throbber.
      $(that).parent().find('.ajax-progress').remove();
      // Enable the button.
      if (!drupalSettings.ai_image_gen.hide_button) {
        $(that).show();
      }
      // Enable the text field.
      $(that).parent('.form-managed-file').find("input[name$='[alt]']").removeAttr('disabled');
    },
    attach: (context) => {
      $('.ai-image-generation').off('click').on('click', function (e) {
        // Set that it is being tracked.
        Drupal.behaviors.aiImageGen.trackedImages[$(this).data('file-id')] = true;
        e.preventDefault();
        // Manually add the throbber.
        let throbber = $('<div class="ajax-progress ajax-progress--throbber"><div class="ajax-progress__throbber">&nbsp;</div><div class="ajax-progress__message">' + Drupal.t('Generating alt text...') + '</div></div>');
        $(this).parent().append(throbber);
        $(this).parent('.form-managed-file').find("input[name$='[alt]']").attr('disabled', 'disabled');
        // Disable the button.
        $(this).hide();
        let that = $(this);
        let lang = drupalSettings.ai_image_gen.lang;
        $.ajax({
          url: drupalSettings.path.baseUrl + 'admin/config/ai/ai_image_gen/generate/' + $(this).data('file-id') + '/' + lang,
          type: 'GET',
          success: function (response) {
            if ('image' in response) {
              // Add handler for displaying generated Image
              $(that).parents('.form-managed-file').find("input[name$='[alt]']").val(response.image);
            }
            Drupal.behaviors.aiImageGen.finishedWorking(that);
          },
          error: function (response) {
            let messenger = new Drupal.Message();
            if ('responseJSON' in response && 'error' in response.responseJSON) {
              messenger.add('Error: ' + response.responseJSON.error, { type: 'warning' });
            }
            else {
              messenger.add(Drupal.t('We could not create an Image, please try again later.'), { type: 'warning' });
            }
            Drupal.behaviors.aiImageGen.finishedWorking(that);
          }
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
