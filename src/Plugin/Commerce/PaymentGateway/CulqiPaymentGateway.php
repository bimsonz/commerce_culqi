<?php

namespace Drupal\commerce_culqi\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_culqi\Service\CulqiService;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Culqi payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "culqi",
 *   label = @Translation("Culqi Payments."),
 *   display_label = @Translation("Culqi"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_culqi\PluginForm\Culqi\PaymentMethodAddForm",
 *     "refund-payment" = "Drupal\commerce_culqi\PluginForm\Culqi\PaymentRefundForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 *   js_library = "commerce_culqi/form",
 * )
 */
class CulqiPaymentGateway extends OffsitePaymentGatewayBase implements CulqiPaymentGatewayInterface {

  protected $culqiService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('commerce_culqi.culqi')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, CulqiService $culqiService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->culqiService = $culqiService;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'publishable_key' => '',
      'secret_key' => '',
      'logo' => 'https://www.culqi.com/wp-content/themes/wp-bootstrap-starter-child/images/logomenu.png',
      'maincolor' => '#0ec1c1',
      'buttontext' => '#ffffff',
      'maintext' => '#4A4A4A',
      'desctext' => '#4A4A4A',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {

    $remoteId = $request->request->get('txn_id');

    // TODO: Validate charge returns.
    //    if ($this->culqiService->validateCharge($remoteId) == FALSE) {
    //      return FALSE;
    //    }

    $paymentStorage = $this->entityTypeManager->getStorage('commerce_payment');

    $authorizationCode = $request->request->get('authorization_code');
    $paymentStatus = $request->request->get('payment_status');
    $payment = $paymentStorage->create([
    // $authorizationCode,
      'state' => 'completed',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->parentEntity->id(),
      'order_id' => $order->id(),
      'remote_id' => $remoteId,
      'remote_state' => $paymentStatus,
    ]);

    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL, string $reason = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    $this->culqiService->createRefund($payment, $this->toMinorUnits($amount), $reason);

    $oldRefundedAmount = $payment->getRefundedAmount();
    $newRefundedAmount = $oldRefundedAmount->add($amount);
    if ($newRefundedAmount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
    }
    else {
      $payment->setState('refunded');
    }

    $payment->setRefundedAmount($newRefundedAmount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['publishable_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publishable Key'),
      '#default_value' => $this->configuration['publishable_key'],
      '#required' => TRUE,
    ];
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Key'),
      '#default_value' => $this->configuration['secret_key'],
      '#required' => TRUE,
    ];

    $form['widget_options'] = [
      '#type' => 'fieldset',
      '#title' => $this
        ->t('Widget Options'),
    ];

    $form['widget_options']['logo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Logo'),
      '#default_value' => $this->configuration['logo'],
    ];

    $form['widget_options']['maincolor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Main Color'),
      '#default_value' => $this->configuration['maincolor'],
    ];

    $form['widget_options']['buttontext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#default_value' => $this->configuration['buttontext'],
    ];

    $form['widget_options']['maintext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Main Text Color'),
      '#default_value' => $this->configuration['maintext'],
    ];

    $form['widget_options']['desctext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description Text Color'),
      '#default_value' => $this->configuration['desctext'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['publishable_key'] = $values['publishable_key'];
      $this->configuration['secret_key'] = $values['secret_key'];
      $this->configuration['logo'] = $values['widget_options']['logo'];
      $this->configuration['maincolor'] = $values['widget_options']['maincolor'];
      $this->configuration['buttontext'] = $values['widget_options']['buttontext'];
      $this->configuration['maintext'] = $values['widget_options']['maintext'];
      $this->configuration['desctext'] = $values['widget_options']['desctext'];
    }
  }

}
