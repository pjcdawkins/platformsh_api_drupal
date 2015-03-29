<?php

class PlatformshApiSubscriptionMetadataController extends EntityDefaultMetadataController {

  public function entityPropertyInfo() {
    $info = parent::entityPropertyInfo();
    $properties = $info['platformsh_api_subscription']['properties'];
    $properties['external_id'] = array(
      'label' => t('External ID'),
      'description' => t('The external ID of the subscription resource.'),
      'type' => 'token',
      'required' => TRUE,
    );
    $properties['url'] = array(
      'label' => t('URL'),
      'description' => t('The URL to the subscription resource.'),
      'type' => 'uri',
      'required' => TRUE,
    );
    $properties['uid'] = array(
      'label' => t('User'),
      'description' => t('The Drupal user associated with the subscription.'),
      'type' => 'user',
      'required' => FALSE,
    );

    // @todo computed properties from $subscription->data

    $properties['created'] = array(
      'label' => t('Created'),
      'description' => t('The date and time when the subscription was created.'),
      'type' => 'date',
      'required' => TRUE,
    );
    $properties['changed'] = array(
      'label' => t('Changed'),
      'description' => t('The date and time when the subscription was last changed.'),
      'type' => 'date',
      'required' => TRUE,
    );


    return $info;
  }
}
