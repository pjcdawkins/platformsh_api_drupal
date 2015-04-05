<?php
/**
 * @file
 * Page callbacks for the Platform.sh API Commerce module.
 */

/**
 * Menu callback for Platform.sh to notify us of a successful subscription.
 *
 * @param \PlatformshApiCommerceSubscriptionLicense $license
 *   The subscription license entity.
 */
function platformsh_api_commerce_activation_callback(\PlatformshApiCommerceSubscriptionLicense $license) {
  // Only act if the license is not yet synchronized.
  if ($license->wrapper()->sync_status->value() !== COMMERCE_LICENSE_SYNCED) {
    if ($resource = $license->getSubscription()) {
      /** @var \Platformsh\Client\Model\Subscription $subscription */
      $subscription = $resource->source();
      $subscription->refresh();
      $license->setStatusFromSubscription($subscription);
    }
  }

  drupal_exit();
}

/**
 * @param \CommerceLicenseBase $license
 *   The subscription license entity.
 *
 * @return bool
 *   TRUE if access is granted, FALSE if denied.
 */
function platformsh_api_commerce_activation_callback_access(\CommerceLicenseBase $license) {
  if (!$license instanceof \PlatformshApiCommerceSubscriptionLicense) {
    return FALSE;
  }

  return isset($_GET['token']) && platformsh_api_commerce_validate_token($_GET['token'], $license);
}
