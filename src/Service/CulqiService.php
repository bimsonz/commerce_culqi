<?php

namespace Drupal\commerce_culqi\Service;

use Culqi\Culqi;
use Culqi\Error\InvalidApiKey;
use Drupal\commerce_culqi\Charge;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;

class CulqiService {

  /**
   * @var \Culqi\Culqi
   */
  protected $api;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  protected $logger;

  /**
   * CulqiService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerFactory) {
    $this->config = $configFactory->get('commerce_payment.commerce_payment_gateway.culqi');
    $this->logger = $loggerFactory->get('commerce_culqi');
  }

  /**
   * Preform server side charge request.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $parameterBag
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function createCharge(ParameterBag $parameterBag) {
    try {
      $apiResponse = $this->getApi()->Charges->create(
        [
          "amount" => $parameterBag->get('amount'),
          "capture" => TRUE,
          "currency_code" => $parameterBag->get('currency_code'),
          "email" => $parameterBag->get('email'),
          "source_id" => $parameterBag->get('source_id'),
          "antifraud_details" => [
            "first_name" => $parameterBag->get('name', 'NN'),
            "last_name" => $parameterBag->get('last_name', 'NN'),
            "address" => $parameterBag->get('address', 'NN'),
            "address_city" => $parameterBag->get('city', 'NN'),
            "country_code" => "PE",
            ],
          ]
      );

      $charge = Charge::fromObject($apiResponse);
    }
    catch (\Exception $exception) {
      $charge = Charge::isInvalid();
      $this->logger->error($exception->getMessage());
    }

    return new JsonResponse($charge);
  }

  /**
   * Preform server side refund request.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   * @param string $amount
   * @param string $reason
   */
  public function createRefund(PaymentInterface $payment, $amount = NULL, $reason = 'solicitud_comprador') {
    try {
      $this->getApi()->Refunds->create(
        [
          "amount" => $amount,
          "charge_id" => $payment->getRemoteId(),
          "reason" => $reason,
        ]
      );
    }
    catch (\Exception $exception) {
      $this->logger->error($exception->getMessage());
      throw new PaymentGatewayException($exception->getMessage());
    }
  }

  public function validateCharge($remoteId) {
    $this->logger->error(print_r($this->getApi()->Charges->get($remoteId), 1));
    return $this->getApi()->Charges->get($remoteId);
  }

  /**
   * Get api client.
   *
   * @return \Culqi\Culqi
   */
  public function getApi() {
    if ($this->api instanceof Culqi) {
      return $this->api;
    }

    try {
      $options = ['api_key' => $this->config->get('configuration.secret_key')];

      $api = new Culqi($options);
      $this->api = $api;
    }
    catch (InvalidApiKey $exception) {
      $this->logger->critical($exception->getMessage());
    }

    return $this->api;
  }

}
