<?php

namespace Drupal\commerce_culqi\PluginForm\Culqi;

use Drupal\commerce_payment\PluginForm\PaymentRefundForm as BasePaymentRefundForm;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;

class PaymentRefundForm extends BasePaymentRefundForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['reason'] = [
      '#type' => 'select',
      '#title' => $this->t('Reason'),
      '#options' => [
        'duplicado' => $this->t('Duplicate'),
        'fraudulento' => $this->t('Fraudulent'),
        'solicitud_comprador' => $this->t('Buyer Request'),
      ],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $amount = Price::fromArray($values['amount']);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_culqi\Plugin\Commerce\PaymentGateway\CulqiPaymentGatewayInterface $paymentGatewayPlugin */
    $paymentGatewayPlugin = $this->plugin;
    $paymentGatewayPlugin->refundPayment($payment, $amount, $values['reason']);
  }

}
