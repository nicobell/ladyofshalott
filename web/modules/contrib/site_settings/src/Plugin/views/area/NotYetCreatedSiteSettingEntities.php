<?php

namespace Drupal\site_settings\Plugin\views\area;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A views area to list site settings entities that have not yet been created.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("not_yet_created_site_settings")
 */
class NotYetCreatedSiteSettingEntities extends AreaPluginBase {

  /**
   * Storage for bundle infos.
   *
   * @var array
   */
  protected array $bundles = [];

  /**
   * Constructs a new NotYetCreatedSiteSettingEntities.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $databaseConnection
   *   The database service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $linkGenerator
   *   The link generator service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $databaseConnection,
    protected LinkGeneratorInterface $linkGenerator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    $this->bundles = $this->entityTypeManager->getStorage('site_setting_entity_type')->loadMultiple();
    $query = $this->databaseConnection->select('site_setting_entity', 'sse');
    $query->addField('sse', 'type');
    $used_bundles = $query->distinct()->execute()->fetchCol();
    $used_bundles = $used_bundles ?: [];
    $missing_bundles = array_diff(array_keys($this->bundles), $used_bundles);
    $build = [];

    // If we have site settings not yet created.
    if ($missing_bundles) {
      $build['table'] = [
        '#type' => 'table',
        '#header' => $this->buildHeader(),
        '#rows' => [],
      ];

      // Sort missing bundles alphabetically by group and label.
      usort($missing_bundles, function ($a, $b) {
        if ($this->bundles[$a]->group == $this->bundles[$b]->group) {
          return ($this->bundles[$a]->label() >= $this->bundles[$b]->label()) ? -1 : 1;
        }
        return $this->bundles[$a]->group >= $this->bundles[$b]->group ? -1 : 1;
      });

      // Boolean to determine whether the 'Settings not yet created' title
      // should be shown.
      foreach ($missing_bundles as $missing) {

        // Settings that have not yet been created rows.
        $url = new Url('entity.site_setting_entity.add_form', [
          'site_setting_entity_type' => $missing,
        ]);
        if ($url->access()) {

          // Add link if user has access.
          $link = [
            '#type' => 'link',
            '#title' => $this->t('Create setting'),
            '#url' => $url,
            '#attributes' => ['class' => ['button']],
          ];

          array_unshift($build['table']['#rows'], [
            'name' => $this->linkGenerator->generate($this->bundles[$missing]->label(), $url),
            'group' => $this->bundles[$missing]->group,
            'operations' => $this->getRenderer()->render($link),
          ]);
        }
      }
    }

    return $build;
  }

  /**
   * Build the table header.
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Not yet created setting');
    $header['group'] = $this->t('Group');
    $header['operations'] = $this->t('Operations');
    return $header;
  }

}
