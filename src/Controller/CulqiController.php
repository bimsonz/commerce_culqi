<?php

namespace Drupal\commerce_culqi\Controller;

use Drupal\commerce_culqi\Service\CulqiService;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handle any serverside Culqi requests.
 */
class CulqiController implements ContainerInjectionInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Culqi Service.
   *
   * @var \Drupal\commerce_culqi\Service\CulqiService
   */
  protected $culqi;

  /**
   * Constructs a new CulqiRedirectController object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   * @param \Drupal\commerce_culqi\Service\CulqiService $culqi
   */
  public function __construct(RequestStack $requestStack, CulqiService $culqi) {
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->culqi = $culqi;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('commerce_culqi.culqi')
    );
  }

  /**
   * Create charge and return response.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function createCharge() {
    return $this->culqi->createCharge($this->currentRequest->request);
  }

}
