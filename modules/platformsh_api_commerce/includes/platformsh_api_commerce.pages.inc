<?php
/**
 * @file
 * Page callbacks for the Platform.sh Commerce module.
 */

/**
 * Menu callback for Platform.sh to notify us of a successful subscription.
 *
 * @param object $license
 *   The subscription license.
 */
function platformsh_api_commerce_activation_callback($license) {
  // Only act if the license is not yet synchronized.
  if ($license->wrapper()->sync_status->value() !== COMMERCE_LICENSE_SYNCED) {
    if ($resource = $license->getSubscription()) {
      watchdog('platformsh_api_commerce', 'Refreshing subscription after activation callback.');
      /** @var \Platformsh\Client\Model\Subscription $subscription */
      $subscription = $resource->source();
      $subscription->refresh();
      $license->setStatusFromSubscription($subscription);
      $license->wrapper()->save();
    }
  }

  drupal_exit();
}
