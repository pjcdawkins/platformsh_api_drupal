<?php

class PlatformshApiSubscriptionController extends EntityAPIController {

  /**
   * {@inheritdoc}
   */
  public function save($entity, DatabaseTransaction $transaction = NULL) {
    if (!isset($entity->created)) {
      $entity->created = REQUEST_TIME;
    }
    if (!isset($entity->changed)) {
      $entity->changed = REQUEST_TIME;
    }

    return parent::save($entity, $transaction);
  }
}
