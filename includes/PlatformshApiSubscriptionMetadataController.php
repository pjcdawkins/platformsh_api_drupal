<?php

class PlatformshApiSubscriptionMetadataController extends EntityDefaultMetadataController {

  public function entityPropertyInfo() {
    $info = parent::entityPropertyInfo();
    $properties = &$info['platformsh_api_subscription']['properties'];

    $properties['url']['type'] = 'uri';

    $properties['uid']['type'] = 'user';

    $properties['refreshed']['type'] = 'date';

    $properties['project_link'] = array(
      'label' => t('Project'),
      'description' => t('A link to the project.'),
      'type' => 'text',
      'getter callback' => 'platformsh_api_subscription_project_link_getter',
      'computed' => TRUE,
      'entity views field' => TRUE,
      'sanitized' => TRUE,
    );

    return $info;
  }
}
