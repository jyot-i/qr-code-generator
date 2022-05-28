<?php

namespace Drupal\qr_code_generator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Cache\Cache;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\NotoSans;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;


/**
 * Provides a block with a QR code.
 *
 * @Block(
 *   id = "qr_code_block",
 *   admin_label = @Translation("QR block"),
 * )
 */
class QRCodeBlock extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * The current route match.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;


  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
  }

   /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $base_url = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface ) {
      $uri = $node->field_app_purchase_link->uri;
      // create QR code.
      $qr_path = $this->getQrData($uri);
      return array(
          '#type' => 'inline_template',
          '#template' => '<img src="'.$qr_path.'" width="200" height="200" alt="qrcode">',
        );
    }
  }

  /**
   * Generate QR image data.
   *
   * @return QR image data
   */
  public function getQrData(string $qr_content) {

    $result = Builder::create()
      ->writer(new PngWriter())
      ->writerOptions([])
      ->data($qr_content)
      ->encoding(new Encoding('UTF-8'))
      ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
      ->size(200)
      ->margin(10)
      ->labelText('Scan this')
      ->labelFont(new NotoSans(12))
      ->labelAlignment(new LabelAlignmentCenter())
      ->build();

    return $result->getDataUri();

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    //With this when your node change your block will rebuild
    if ($node = $this->routeMatch->getParameter('node')) {
    //if there is node add its cachetag
      return Cache::mergeTags(parent::getCacheTags(), array('node:' . $node->id()));
    } else {
      //Return default tags instead.
      return parent::getCacheTags();
    }
  }


  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    //if you depends on \Drupal::routeMatch()
    //you must set context of this block with 'route' context tag.
    //Every new route this block will rebuild
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }


}