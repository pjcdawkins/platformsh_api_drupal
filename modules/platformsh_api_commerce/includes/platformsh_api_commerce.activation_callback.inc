<?php
/**
 * @file
 * Functions related to the Platform.sh subscription activation callback.
 */

/**
 * Menu callback for Platform.sh to notify us of a successful subscription.
 *
 * @param int $license_id
 *   The subscription license entity ID.
 */
function platformsh_api_commerce_activation_callback($license_id) {
  $license = entity_load_single('commerce_license', $license_id);
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

/**
 * Check access for the subscription activation callback.
 *
 * @param int $license_id
 *   The subscription license entity ID.
 *
 * @return bool
 *   TRUE if access is granted, FALSE if denied.
 */
function platformsh_api_commerce_activation_callback_access($license_id) {
  $license = entity_load_single('commerce_license', $license_id);
  if (!$license instanceof \PlatformshApiCommerceSubscriptionLicense) {
    return FALSE;
  }

  return isset($_GET['token']) && platformsh_api_commerce_validate_token($_GET['token'], $license);
}

/**
 * Generate a token to validate the activation callback.
 *
 * @param \CommerceLicenseBase $license
 *   The subscription license entity.
 *
 * @return string
 *   A token.
 */
function platformsh_api_commerce_get_token(\CommerceLicenseBase $license) {
  $data = serialize(array($license->license_id, $license->uid));
  $key = drupal_get_private_key() . '-' . drupal_get_hash_salt();
  return drupal_hmac_base64($data, $key);
}

/**
 * Validate a token in the activation callback.
 *
 * @param string $token
 *   A token passed in the activation callback.
 * @param \CommerceLicenseBase $license
 *   The subscription license entity.
 *
 * @return bool
 *   TRUE if valid, FALSE otherwise.
 */
function platformsh_api_commerce_validate_token($token, \CommerceLicenseBase $license) {
  return $token === platformsh_api_commerce_get_token($license);
}

/**
 * Get the activation callback URL.
 *
 * @param \CommerceLicenseBase $license
 *   The subscription license entity.
 *
 * @return string
 *   The absolute URL to the activation callback.
 */
function platformsh_api_commerce_get_activation_callback(\CommerceLicenseBase $license) {
  return url(
    'platformsh/activation-callback/' . $license->license_id,
    array(
      'absolute' => TRUE,
      'query' => array(
        'token' => platformsh_api_commerce_get_token($license),
      ),
    )
  );
}
