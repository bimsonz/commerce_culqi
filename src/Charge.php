<?php

namespace Drupal\commerce_culqi;

class Charge implements \JsonSerializable {

  /**
   * @var string
   */
  public static $successCode = 'AUT0000';

  /**
   * @var array
   */
  protected $data;

  /**
   * Invalid charge.
   *
   * @return Charge
   */
  public static function isInvalid() {
    return new Charge(['validate' => FALSE]);
  }

  /**
   * Static helper to create Charge from stdClass.
   *
   * @param \stdClass $data
   * @return Charge
   */
  public static function fromObject(\stdClass $data) {
    return new Charge((array) $data);
  }

  /**
   * Charge constructor.
   *
   * @param $data
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    if ($this->isValid()) {
      return $this->valid();
    }

    return $this->isInvalid();
  }

  /**
   * Check if charge is valid.
   *
   * @return bool
   */
  public function isValid() {
    if (array_key_exists('outcome', $this->data) == FALSE) {
      return FALSE;
    }

    if (property_exists($this->data['outcome'], 'code') == FALSE) {
      return FALSE;
    }

    if ($this->data['outcome']->code == self::$successCode) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Return invalid charge data.
   *
   * @return array
   */
  protected function invalid() {
    $return = ['validate' => FALSE];

    if (
      array_key_exists('outcome', $this->data) == TRUE &&
      property_exists($this->data['outcome'], 'merchant_message') == TRUE
    ) {
      $return['message'] = $this->data['outcome']->merchant_message;
    }

    return $return;
  }

  /**
   * Return valid charge data.
   *
   * @return array
   */
  protected function valid() {
    return [
      'validate' => TRUE,
      'txn_id' => $this->data['id'],
      'authorization_code' => $this->data['authorization_code'],
    // $this->data['outcome']['type'],
      'payment_status' => 'venta_exitosa',
      'message' => $this->data['outcome']->merchant_message,
      'charge' => $this->data,
    ];
  }

}
