/**
 * @file
 * Javascript to generate Culqi token in PCI-compliant way.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the commerceCulqiForm behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop object cardNumber
   *   Culqi card number element.
   * @prop object cardExpiry
   *   Culqi card expiry element.
   * @prop object cardCvc
   *   Culqi card cvc element.
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the commerceCulqiForm behavior.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches the commerceCulqiForm behavior.
   *
   * @see Drupal.commerceCulqi
   */
  Drupal.behaviors.commerceCulqiForm = {
    attach: function (context) {
      if (!drupalSettings.culqiCommerce.settings || !drupalSettings.culqiCommerce.settings.publishableKey) {
        return;
      }

      let CulqiOptions = drupalSettings.culqiCommerce.options;
      let CulqiSettings = drupalSettings.culqiCommerce.settings;
      let CulqiData = drupalSettings.culqiCommerce.data;

      Culqi.publicKey = CulqiSettings.publishableKey;

      Culqi.settings({
          title: CulqiSettings.title,
          currency: CulqiSettings.currency,
          description: CulqiSettings.description,
          amount: CulqiSettings.amount
      });

      Culqi.options({
        style: {
          logo: CulqiOptions.logo,
          maincolor: CulqiOptions.maincolor,
          buttontext: CulqiOptions.buttontext,
          maintext: CulqiOptions.maintext,
          desctext: CulqiOptions.desctext,
        }
      });

      $('.payment-culqi').on('click', function (e) {
        Culqi.open();
        e.preventDefault();
      });

      $(document).ready(function() {
        if(Culqi) {
          Culqi.open();
        }
      });

      function culqi() {
        $(document).ajaxStart(function(){
          run_waitMe(Drupal.t('Processing Payment...'));
        });

        if (Culqi.token) {
          let data = {
            "source_id": Culqi.token.id,
            "email": Culqi.token.email,
            "amount": CulqiSettings.amount,
            "currency_code": CulqiSettings.currency,
            "first_name": CulqiData.first_name,
            "last_name": CulqiData.last_name,
            "address": CulqiData.address,
            "city": CulqiData.city,
            "return": CulqiSettings.returnUrl,
          };

          jQuery.post(CulqiSettings.createChargeUrl, data).done(function(chargeResponse){
            if(chargeResponse['validate']) {
              let chargeData = {
                txn_id: chargeResponse['txn_id'],
                authorization_code: chargeResponse['authorization_code'],
                payment_status: chargeResponse['payment_status'],
              };
              setTimeout(function() {
                jQuery.redirect(CulqiSettings.returnUrl, chargeData, "POST");
                }, 100);
            }
            else {
              $('body').waitMe('hide');
              alert(Drupal.t('Payment request invalid, please try again.'));
            }
          }).fail(function(chargeResponse) {
            $('body').waitMe('hide');
            alert(Drupal.t('Failed to process your payment, please try again.'));
          })
        }
      }
      window.culqi = culqi;
      },
    detach: function (context, settings, trigger) {
    }
  };

  $.extend(Drupal.theme, /** @lends Drupal.theme */{
    commerceCulqiError: function (message) {
      return $('<div class="messages messages--error"></div>').html(message);
    }
  });

})(jQuery, Drupal, drupalSettings);

function run_waitMe(message){
  jQuery('body').waitMe({
    effect: 'orbit',
    text: message,
    bg: 'rgba(255,255,255,0.7)',
    color:'#28d2c8'
  });
}
