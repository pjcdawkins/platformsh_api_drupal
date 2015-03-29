<?php

class PlatformshApiSubscription extends Entity {

  public $subscription_id;
  public $type;
  public $external_id;
  public $url;
  public $created;
  public $changed;
  public $data = array();
}
