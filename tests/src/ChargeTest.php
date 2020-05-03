<?php

use Drupal\commerce_culqi\Charge;
use Drupal\Tests\UnitTestCase;

class ChargeTest extends UnitTestCase {

  /**
   * @dataProvider chargeProvider
   */
  public function testChargeValidity($data, $expected) {

    $charge = new Charge($data);

    $this->assertEquals($expected, $charge->isValid());
  }

  /**
   * Data provider for testAccess().
   *
   * @return array
   *   A list of testAccess method arguments.
   */
  public function chargeProvider() {
    $successfulChargeObject = new stdClass();
    $successfulChargeObject->code = Charge::$successCode;

    $data = [
      [
        ['outcome' => $successfulChargeObject],
        TRUE,
      ],
      [
        ['no' => 'data'],
        FALSE,
      ]
    ];

    return $data;
  }
}
