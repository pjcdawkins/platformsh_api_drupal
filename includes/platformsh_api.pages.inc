<?php
/**
 * @file
 * Page callbacks for the Platform.sh API module.
 */

/**
 * Configuration form.
 */
function platformsh_api_config_form($form, &$form_state) {
  $form['platformsh_api_token'] = array(
    '#type' => 'textfield',
    '#title' => t('API token'),
    '#description' => t('You can get an API token from your Platform.sh account settings.'),
    '#required' => TRUE,
  );

  if (variable_get('platformsh_api_token')) {
    $form['platformsh_api_token']['#required'] = FALSE;
    $form['platformsh_api_token']['#description'] .= '<br />'
      . t('Leave blank to use the already set token.');
  }

  return system_settings_form($form);
}

/**
 * Validate the configuration form.
 */
function platformsh_api_config_form_validate(&$form, &$form_state) {
  $api_token = variable_get('platformsh_api_token', $form_state['values']['platformsh_api_token']);
  if (!empty($api_token)) {
    if (platformsh_api_validate_token($api_token)) {
      drupal_set_message(t('The API token was successfully validated.'));
    }
    else {
      form_set_error('platformsh_api_token', t('Invalid API token'));
      return;
    }
  }

  if (empty($form_state['values']['platformsh_api_token'])) {
    unset($form_state['values']['platformsh_api_token']);
  }
}

/**
 * Page callback for listing subscriptions.
 */
function platformsh_api_list_subscriptions() {
  $client = platformsh_api_client();
  try {
    $subscriptions = $client->getSubscriptions();
  }
  catch (Exception $e) {
    platformsh_api_handle_exception($e);
    $subscriptions = array();
  }

  $header = array(t('ID'), t('Status'), t('Plan'), t('Cluster'), t('Project ID'), t('Project Title'));

  $rows = array();
  foreach ($subscriptions as $subscription) {
    $project = NULL;
    if ($subscription->getProperty('project_ui', FALSE)) {
      $title = $subscription->project_title ?: $subscription->project_id;
      $project = l($title, $subscription->getProperty('project_ui'));
    }

    $row = array(
      check_plain($subscription->id),
      check_plain($subscription->status),
      check_plain($subscription->plan),
      check_plain($subscription->project_cluster_label),
      check_plain($subscription->project_id),
      $project,
    );
    $rows[] = $row;
  }

  if (empty($rows)) {
    $rows[0][0] = array(
      'data' => t('No subscriptions found.'),
      'colspan' => count($header),
    );
  }

  $output = array(
    'subscriptions' => array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ),
  );

  return $output;
}
