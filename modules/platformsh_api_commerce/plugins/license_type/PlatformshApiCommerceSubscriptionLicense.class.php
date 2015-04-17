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

    $fields['platformsh_license_region']['field'] = array(
      'cardinality' => 1,
      'translatable' => FALSE,
      'module' => 'list',
      'type' => 'list_text',
      'settings' => array(
        'allowed_values_function' => 'platformsh_api_subscription_region_options_list',
      ),
    );
    $fields['platformsh_license_region']['instance'] = array(
      'label' => t('Project region'),
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
    $subscription = $this->getSubscription();
    if ($subscription) {
      $link = $subscription->wrapper()->project_link->value();
      if ($link) {
        $output['project'] = array(
          '#prefix' => '<span class="access-details">',
          '#markup' => t('Project: !link', array('!link' => $link)),
          '#suffix' => '</span>',
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
      case COMMERCE_LICENSE_CREATED:
      case COMMERCE_LICENSE_PENDING:
      case COMMERCE_LICENSE_ACTIVE:
        if ($resource = $this->getSubscription()) {
          $this->synchronizeExistingSubscription($resource);
        }
        else {
          $this->createNewSubscription();
        }
        return TRUE;

      case COMMERCE_LICENSE_EXPIRED:
      case COMMERCE_LICENSE_REVOKED:
        return $this->deleteSubscription();

      case COMMERCE_LICENSE_SUSPENDED:
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
    $this->setStatusFromSubscription($subscription);

    $this->save();

    watchdog('platformsh_api_commerce', 'Synced. Subscription status: @status1, sync status: @status2, license status: @status3', array(
      '@status1' => $subscription->getStatus(),
      '@status2' => $this->wrapper()->sync_status->value(),
      '@status3' => $this->status,
    ));
  }

  /**
   * Get the subscription associated with this license.
   *
   * @return \PlatformshApiResource|bool
   *   The subscription resource entity, or FALSE on failure.
   */
  public function getSubscription() {
    return $this->wrapper()->platformsh_license_subscription->value();
  }

  /**
   * @param \Platformsh\Client\Model\Subscription $subscription
   */
  public function setStatusFromSubscription(\Platformsh\Client\Model\Subscription $subscription) {
    $failed_statuses = array(
      \Platformsh\Client\Model\Subscription::STATUS_FAILED,
      \Platformsh\Client\Model\Subscription::STATUS_DELETED,
      \Platformsh\Client\Model\Subscription::STATUS_SUSPENDED,
    );

    if ($subscription->isActive()) {
      $this->wrapper()->sync_status = COMMERCE_LICENSE_SYNCED;
      $this->status = COMMERCE_LICENSE_ACTIVE;
    }
    elseif ($subscription->isPending()) {
      $this->wrapper()->sync_status = COMMERCE_LICENSE_NEEDS_SYNC;
      $this->status = COMMERCE_LICENSE_PENDING;
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

    $activation_callback = NULL;
    if (variable_get('platformsh_api_commerce_use_callback', TRUE)) {
      $activation_callback = array(
        'uri' => platformsh_api_commerce_get_activation_callback($this),
      );
    }

    try {
      $subscription = $client->createSubscription(
        $this->wrapper()->platformsh_license_region->value(),
        $this->wrapper()->platformsh_license_plan->value(),
        $this->wrapper()->platformsh_license_project_title->value() ?: NULL,
        NULL,
        NULL,
        $activation_callback
      );
    } catch (\Exception $e) {
      $this->wrapper()->sync_status = COMMERCE_LICENSE_SYNC_FAILED_RETRY;

      // If the error is an internal, code-related, one, don't retry.
      if ($e instanceof \InvalidArgumentException) {
        $this->wrapper()->sync_status = COMMERCE_LICENSE_SYNC_FAILED;
      }

      $message = $e->getMessage();
      if ($message == 'Not logged in') {
        $message = 'API token not configured';
      }

      watchdog('platformsh_api_commerce', 'Failed to create subscription for license @id: @message', array(
        '@id' => $this->license_id,
        '@message' => $message,
      ), WATCHDOG_ERROR);
      return;
    }

    // Wait for the subscription to become active.
    if (variable_get('platformsh_api_commerce_wait', TRUE)) {
      watchdog('platformsh_api_commerce', 'Waiting for subscription activation.');
      try {
        $subscription->wait(NULL, 1);
      }
      catch (\Exception $e) {
        watchdog('platformsh_api_commerce', 'Exception caught while waiting for subscription activation on license @id: @message', array(
          '@id' => $this->license_id,
          '@message' => $e->getMessage(),
        ), WATCHDOG_ERROR);
      }
    }

    platformsh_api_save_resources(array($subscription), 'subscription', FALSE, $this->wrapper()->owner->value());
    $resource = platformsh_api_load_resource_by_external_id($subscription->id, 'subscription');
    if (!$resource) {
      $this->wrapper()->sync_status = COMMERCE_LICENSE_SYNC_FAILED_RETRY;
      // This should never happen.
      throw new \RuntimeException('Failed to create subscription');
    }

    $this->setStatusFromSubscription($subscription);

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
    if (!$resource = $this->getSubscription()) {
      return FALSE;
    }

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

    return $this->save();
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutCompletionMessage() {
    $message = '';
    $sync_status = $this->wrapper()->sync_status->value();
    switch ($sync_status) {
      case COMMERCE_LICENSE_NEEDS_SYNC:
        $message = t("Please wait while we create your Platform.sh project.");
        break;
      case COMMERCE_LICENSE_SYNCED:
        $message = t('Your Platform.sh project has been successfully created.');
        $message .= '<br />' . $this->accessDetails();
        break;
      case COMMERCE_LICENSE_SYNC_FAILED_RETRY:
        $message = t('Your Platform.sh project has been queued for processing.');
        break;
      case COMMERCE_LICENSE_SYNC_FAILED:
        $message = t('Synchronization failed. Details have been logged.');
        break;
    }

    return $message;
  }
}
