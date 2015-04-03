<?php
/**
 * @file
 * Platform.sh Subscription license plugin.
 */

class PlatformshApiCommerceSubscriptionLicense extends CommerceLicenseRemoteBase {

  /**
   * Implements EntityBundlePluginProvideFieldsInterface::fields().
   */
  static function fields() {
    $fields = parent::fields();
    $fields['platformsh_api_subscription']['field'] = array(
      'cardinality' => 1,
      'translatable' => FALSE,
      'module' => 'text',
      'type' => 'text',
    );

    return $fields;
  }

  /**
   * Implements CommerceLicenseInterface::accessDetails().
   */
  public function accessDetails() {
    $output = array();
    $subscription_id = $this->wrapper->platformsh_api_subscription->value();
    $subscription = platformsh_api_get_subscription($subscription_id);
    if ($subscription) {
      $link = $subscription->wrapper()->project_link->value();
      if ($link) {
        $output['project'] = array(
          '#markup' => $link,
        );
      }
    }

    return drupal_render($output);
  }
}
