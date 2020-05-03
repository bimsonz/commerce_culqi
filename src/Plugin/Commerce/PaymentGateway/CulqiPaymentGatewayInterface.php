<?php

namespace Drupal\commerce_culqi\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_price\Price;

/**
 * Provides the interface for the Culqi payment gateway.
 */
interface CulqiPaymentGatewayInterface extends OffsitePaymentGatewayInterface, SupportsRefundsInterface {

  public function refundPayment(PaymentInterface $payment, Price $amount = NULL, string $reason = NULL);

}
