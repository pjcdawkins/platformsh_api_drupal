<?php
/**
 * @file
 * Default views for the Platform.sh API module.
 */

/**
 * Implements hook_views_default_views().
 */
function platformsh_api_views_default_views() {
  $views = array();

  $view = new view();
  $view->name = 'platformsh_api_subscriptions';
  $view->description = '';
  $view->tag = 'default';
  $view->base_table = 'platformsh_api_subscription';
  $view->human_name = 'Subscriptions';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Subscriptions';
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '10';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'external_id' => 'external_id',
    'created' => 'created',
  );
  $handler->display->display_options['style_options']['default'] = 'external_id';
  $handler->display->display_options['style_options']['info'] = array(
    'external_id' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'created' => array(
      'sortable' => 1,
      'default_sort_order' => 'desc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
  );
  $handler->display->display_options['style_options']['empty_table'] = TRUE;
  /* No results behavior: Global: Text area */
  $handler->display->display_options['empty']['area']['id'] = 'area';
  $handler->display->display_options['empty']['area']['table'] = 'views';
  $handler->display->display_options['empty']['area']['field'] = 'area';
  $handler->display->display_options['empty']['area']['label'] = 'No subscriptions found.';
  $handler->display->display_options['empty']['area']['empty'] = TRUE;
  $handler->display->display_options['empty']['area']['content'] = 'No subscriptions found.';
  $handler->display->display_options['empty']['area']['format'] = 'plain_text';
  /* Field: Platform.sh Subscription: Label */
  $handler->display->display_options['fields']['external_id']['id'] = 'external_id';
  $handler->display->display_options['fields']['external_id']['table'] = 'platformsh_api_subscription';
  $handler->display->display_options['fields']['external_id']['field'] = 'external_id';
  $handler->display->display_options['fields']['external_id']['separator'] = '';
  /* Field: Platform.sh Subscription: Refreshed */
  $handler->display->display_options['fields']['created']['id'] = 'created';
  $handler->display->display_options['fields']['created']['table'] = 'platformsh_api_subscription';
  $handler->display->display_options['fields']['created']['field'] = 'refreshed';
  $handler->display->display_options['fields']['created']['date_format'] = 'time ago';
  $handler->display->display_options['fields']['created']['second_date_format'] = 'long';
  /* Field: Platform.sh Subscription: Project */
  $handler->display->display_options['fields']['project_link']['id'] = 'project_link';
  $handler->display->display_options['fields']['project_link']['table'] = 'views_entity_platformsh_api_subscription';
  $handler->display->display_options['fields']['project_link']['field'] = 'project_link';
  $handler->display->display_options['fields']['project_link']['link_to_entity'] = 0;
  /* Field: Platform.sh Subscription: Usage */
  $handler->display->display_options['fields']['usage']['id'] = 'usage';
  $handler->display->display_options['fields']['usage']['table'] = 'views_entity_platformsh_api_subscription';
  $handler->display->display_options['fields']['usage']['field'] = 'usage';
  $handler->display->display_options['fields']['usage']['link_to_entity'] = 0;

  /* Display: Embed */
  $handler = $view->new_display('embed', 'Embed', 'embed_1');

  $views[$view->name] = $view;

  return $views;
}
