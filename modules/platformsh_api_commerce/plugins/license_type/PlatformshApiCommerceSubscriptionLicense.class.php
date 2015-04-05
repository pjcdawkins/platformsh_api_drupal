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
    // Reference to the subscription.
    $fields['platformsh_license_subscription']['field'] = array(
      'cardinality' => 1,
      'translatable' => FALSE,
      'settings' => array(
        'handler' => 'base',
        'target_type' => 'platformsh_api_resource',
        'target_bundles' => array('subscription'),
      ),
      'module' => 'entityreference',
      'type' => 'entityreference',
    );
    $fields['platformsh_license_subscription']['instance'] = array(
      'label' => t('Subscription'),
      'display' => array(),
    );

    $fields['platformsh_license_cluster']['field'] = array(
      'cardinality' => 1,
      'translatable' => FALSE,
      'module' => 'list',
      'type' => 'list_text',
      'settings' => array(
        'allowed_values_function' => 'platformsh_api_subscription_cluster_options_list',
      ),
    );
    $fields['platformsh_license_cluster']['instance'] = array(
      'label' => t('Project cluster'),
      'required' => TRUE,
      'widget' => array(
        'active' => TRUE,
        'module' => 'options',
        'settings' => array(),
        'type' => 'options_select',
      ),
    );

    $fields['platformsh_license_plan']['field'] = array(
      'cardinality' => 1,
      'translatable' => FALSE,
      'module' => 'list',
      'type' => 'list_text',
      'settings' => array(
        'allowed_values_function' => 'platformsh_api_subscription_plan_options_list',
      ),
    );
    $fields['platformsh_license_plan']['instance'] = array(
      'label' => t('Project plan'),
      'required' => TRUE,
      'widget' => array(
        'active' => TRUE,
        'module' => 'options',
        'settings' => array(),
        'type' => 'options_select',
      ),
    );

    $fields['platformsh_license_project_title']['field'] = array(
      'cardinality' => 1,
      'translatable' => FALSE,
      'module' => 'text',
      'type' => 'text',
    );
    $fields['platformsh_license_project_title']['instance'] = array(
      'label' => t('Initial project title'),
      'required' => FALSE,
      'display' => array(),
    );

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigurable() {
    // Allow the user to configure license fields on the Add to Cart form.
    // See the Commerce License docs: https://www.drupal.org/node/2039687
    return TRUE;
  }

  /**
   * Implements CommerceLicenseInterface::accessDetails().
   */
  public function accessDetails() {
    $output = array();

    /** @var \PlatformshApiResource $subscription */
    $subscription = $this->wrapper->platformsh_license_subscription->value();
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

  /**
   * {@inheritdoc}
   */
  public function synchronize() {
    switch ($this->status) {
      case COMMERCE_LICENSE_PENDING:
      case COMMERCE_LICENSE_SYNC_FAILED_RETRY:
        if ($resource = $this->wrapper()->platformsh_license_subscription->value()) {
          $this->synchronizeExistingSubscription($resource);
        }
        else {
          $this->createNewSubscription();
        }
        return TRUE;

      case COMMERCE_LICENSE_REVOKED:
        return $this->deleteSubscription();

      case COMMERCE_LICENSE_EXPIRED:
      default:
        return FALSE;
    }
  }

  /**
   * Synchronize an existing subscription.
   *
   * @param \PlatformshApiResource $subscription_resource
   *   The subscription resource entity.
   */
  protected function synchronizeExistingSubscription(\PlatformshApiResource $subscription_resource) {
    watchdog('platformsh_api_commerce', 'Syncing license @id1 with existing subscription @id2', array(
      '@id1' => $this->license_id,
      '@id2' => $subscription_resource->external_id,
    ));

    /** @var \Platformsh\Client\Model\Subscription $subscription */
    $subscription = $subscription_resource->source();

    $subscription->refresh();
    $this->setSyncStatusFromSubscription($subscription);

    $this->save();

    watchdog('platformsh_api_commerce', 'Synced. Subscription status: @status1, license status: @status2', array(
      '@status1' => $subscription->getStatus(),
      '@status2' => $this->wrapper()->sync_status->value(),
    ));
  }

  /**
   * @param \Platformsh\Client\Model\Subscription $subscription
   */
  protected function setSyncStatusFromSubscription(\Platformsh\Client\Model\Subscription $subscription) {
    $failed_statuses = array(
      \Platformsh\Client\Model\Subscription::STATUS_FAILED,
      \Platformsh\Client\Model\Subscription::STATUS_DELETED,
      \Platformsh\Client\Model\Subscription::STATUS_SUSPENDED,
    );

    if ($subscription->isActive()) {
      $this->wrapper()->sync_status = COMMERCE_LICENSE_SYNCED;
    }
    elseif ($subscription->isPending()) {
      $this->wrapper()->sync_status = COMMERCE_LICENSE_SYNC_FAILED_RETRY;
    }
    elseif (in_array($subscription->getStatus(), $failed_statuses)) {
      $this->wrapper()->sync_status = COMMERCE_LICENSE_SYNC_FAILED;
    }
  }

  /**
   * Create a new subscription.
   *
   * @throws \RuntimeException
   *   If the subscription cannot be created.
   */
  protected function createNewSubscription() {
    watchdog('platformsh_api_commerce', 'Creating subscription for license @id', array(
      '@id' => $this->license_id,
    ));

    $client = platformsh_api_client();

    try {
      $subscription = $client->createSubscription(
        $this->wrapper()->platformsh_license_cluster->value(),
        $this->wrapper()->platformsh_license_plan->value(),
        $this->wrapper()->platformsh_license_project_title->value() ?: NULL,
        NULL,
        NULL,
        array(
          'uri' => url('platformsh-api/callback/' . $this->license_id, array(
            'absolute' => TRUE,
            'query' => array(
              'token' => drupal_get_token(),
            ),
          )),
        )
      );
    } catch (\Exception $e) {
      $this->wrapper()->sync_status = COMMERCE_LICENSE_SYNC_FAILED_RETRY;

      $message = $e->getMessage();
      if ($message == 'Not logged in') {
        $message = 'API token not configured';
      }

      watchdog('platformsh_api_commerce', 'Failed to create subscription for license @id: @message', array(
        '@id' => $this->license_id,
        '@message' => $message,
      ));
      return;
    }

    platformsh_api_save_resources(array($subscription), 'subscription', FALSE, $this->wrapper()->owner->value());
    $resource = platformsh_api_load_resource_by_external_id($subscription->id, 'subscription');
    if (!$resource) {
      $this->wrapper()->sync_status = COMMERCE_LICENSE_SYNC_FAILED_RETRY;
      throw new \RuntimeException('Failed to create subscription');
    }

    $this->setSyncStatusFromSubscription($subscription);

    $this->wrapper()->platformsh_license_subscription = $resource;

    $this->save();

    watchdog('platformsh_api_commerce', 'Created. License @id1, subscription @id2', array(
      '@id1' => $this->license_id,
      '@id2' => $subscription->id,
    ));
  }

  /**
   * Delete a subscription.
   *
   * @return bool
   *   Whether the subscription was successfully deleted.
   */
  protected function deleteSubscription() {
    if ($resource = $this->wrapper()->platformsh_license_subscription->value()) {

      watchdog('platformsh_api_commerce', 'Deleting subscription. License @id1, subscription @id2', array(
        '@id1' => $this->license_id,
        '@id2' => $resource->external_id,
      ));

      try {
        $resource->source()->delete();
        $this->wrapper()->sync_status = COMMERCE_LICENSE_SYNCED;

        watchdog('platformsh_api_commerce', 'Deleted. License @id1, subscription @id2', array(
          '@id1' => $this->license_id,
          '@id2' => $resource->external_id,
        ));
      } catch (\GuzzleHttp\Exception\BadResponseException $e) {
        $this->wrapper()->sync_status = COMMERCE_LICENSE_SYNC_FAILED;
      }

      $this->save();
      return TRUE;
    }

    return FALSE;
  }
}
