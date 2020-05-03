<?php

namespace Drupal\commerce_culqi\PluginForm\Culqi;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class PaymentMethodAddForm extends PaymentOffsiteForm {

  /**
  * {@inheritdoc}
  */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->getEntity();
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $paymentGatewayPlugin */
    $paymentGatewayPlugin = $payment->getPaymentGateway()->getPlugin();
    $paymentGatewayConfig = $paymentGatewayPlugin->getConfiguration();

    $order = $payment->getOrder();
    $orderId = $order->id();

    $form['actions']['pay'] = [
      '#type' => 'button',
      '#value' => $this->t('Pay Now'),
      '#attributes' => [
        'button',
        'btn',
        'btn-primary',
        'icon-arrow',
        'payment-culqi',
      ],
    ];

    $backUrl = Url::fromRoute(
      'commerce_checkout.form', ['commerce_order' => $orderId, 'step' => 'review']
    );
    $form['actions']['back']['#suffix'] = Link::fromTextAndUrl($this->t('Go back'), $backUrl)->toString();

    $address = $order->getBillingProfile()->get('address');

    $form['#attached']['library'][] = 'commerce_culqi/form';
    $form['#attached']['drupalSettings']['culqiCommerce'] = [
      'settings' => [
        'publishableKey' => $paymentGatewayConfig['publishable_key'],
        'title' => \Drupal::config('system.site')->get('name'),
        'currency' => $payment->getAmount()->getCurrencyCode(),
        'description' => $this->t('Order #@orderId', ['@orderId' => $orderId]),
        'amount' => ($payment->getAmount()->getNumber() * 100),
        'createChargeUrl' => Url::fromRoute('commerce_culqi.create_charge')->toString(),
        'returnUrl' => $form['#return_url'],
      ],
      'options' => [
        'logo' => $paymentGatewayConfig['logo'],
        'maincolor' => $paymentGatewayConfig['maincolor'],
        'buttontext' => $paymentGatewayConfig['buttontext'],
        'maintext' => $paymentGatewayConfig['maintext'],
        'desctext' => $paymentGatewayConfig['desctext'],
      ],
      'data' => [
        'first_name' => $address->given_name,
        'last_name' => $address->family_name,
        'address' => $address->address_line1,
        'city' => $address->locality,
        'province' => $address->administrative_area,
      ],
    ];

    return $form;
  }

}
