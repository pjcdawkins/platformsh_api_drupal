<?php

class PlatformshApiResourceMetadataController extends EntityDefaultMetadataController {

  public function entityPropertyInfo() {
    $info = parent::entityPropertyInfo();
    $properties = &$info['platformsh_api_resource']['properties'];

    $properties['url']['type'] = 'uri';

    $properties['uid']['type'] = 'user';

    $properties['refreshed']['type'] = 'date';

    $info['platformsh_api_resource']['bundles']['subscription']['properties'] = array();
    $subscription_properties = &$info['platformsh_api_resource']['bundles']['subscription']['properties'];

    $subscription_properties['project_link'] = array(
      'label' => t('Project'),
      'description' => t('A link to the project.'),
      'type' => 'text',
      'getter callback' => 'platformsh_api_subscription_project_link_getter',
      'computed' => TRUE,
      'entity views field' => TRUE,
      'sanitized' => TRUE,
    );

    $subscription_properties['usage'] = array(
      'label' => t('Usage'),
      'description' => t('Usage information for the subscription.'),
      'type' => 'text',
      'getter callback' => 'platformsh_api_subscription_usage_getter',
      'computed' => TRUE,
      'entity views field' => TRUE,
      'sanitized' => TRUE,
    );

    return $info;
  }
}
