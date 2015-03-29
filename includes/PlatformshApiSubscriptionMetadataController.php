<?php

class PlatformshApiSubscriptionMetadataController extends EntityDefaultMetadataController {

  public function entityPropertyInfo() {
    $info = parent::entityPropertyInfo();
    $properties = &$info['platformsh_api_subscription']['properties'];

    $properties['url']['type'] = 'uri';

    $properties['uid']['type'] = 'user';

    $properties['refreshed']['type'] = 'date';

    $properties['data'] = array(
      'label' => t('Data'),
      'description' => t('Subscription data.'),
      'computed' => TRUE,
      'type' => 'text',
      'getter callback' => 'platformsh_api_subscription_data_getter',
      'entity views field' => TRUE,
    );

    return $info;
  }
}
