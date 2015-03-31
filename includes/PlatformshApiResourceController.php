<?php

class PlatformshApiResourceController extends EntityAPIController {

  /**
   * {@inheritdoc}
   */
  public function save($entity, DatabaseTransaction $transaction = NULL) {
    if (!isset($entity->refreshed)) {
      $entity->refreshed = REQUEST_TIME;
    }

    return parent::save($entity, $transaction);
  }
}
