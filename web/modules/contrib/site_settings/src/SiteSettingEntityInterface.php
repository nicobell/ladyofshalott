<?php

namespace Drupal\site_settings;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Site Setting entities.
 *
 * @ingroup site_settings
 */
interface SiteSettingEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface, RevisionLogInterface {

  /**
   * Gets the Site Setting type.
   *
   * @return string
   *   The Site Setting type.
   */
  public function getType(): string;

  /**
   * Gets the Site Setting name.
   *
   * @return string
   *   Name of the Site Setting.
   */
  public function getName(): string;

  /**
   * Sets the Site Setting name.
   *
   * @param string $name
   *   The Site Setting name.
   *
   * @return \Drupal\site_settings\SiteSettingEntityInterface
   *   The called Site Setting entity.
   */
  public function setName(string $name): SiteSettingEntityInterface;

  /**
   * Gets the Site Setting creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Site Setting.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the Site Setting creation timestamp.
   *
   * @param int $timestamp
   *   The Site Setting creation timestamp.
   *
   * @return \Drupal\site_settings\SiteSettingEntityInterface
   *   The called Site Setting entity.
   */
  public function setCreatedTime(int $timestamp): SiteSettingEntityInterface;

  /**
   * Gets the Site Setting description.
   *
   * @param string $description
   *   The description of the site setting.
   *
   * @return \Drupal\site_settings\SiteSettingEntityInterface
   *   The called Site Setting entity.
   */
  public function setDescription($description);

  /**
   * Sets the Site Setting description.
   *
   * @return \Drupal\site_settings\SiteSettingEntityInterface
   *   The called Site Setting entity.
   */
  public function getDescription();

  /**
   * Get the Group machine name.
   *
   * @return string
   *   The machine name of the Group.
   */
  public function getGroup(): string;

  /**
   * Set the Group and return the setting.
   *
   * @return \Drupal\site_settings\SiteSettingEntityInterface
   *   The called Site Setting entity.
   */
  public function setGroup($group): SiteSettingEntityInterface;

}
