<?php

class PlatformshApiResource extends Entity {

  public $resource_id;
  public $type;
  public $external_id;
  public $url;
  public $refreshed;
  public $data = array();
  public $uid;

  protected $source;

  /**
   * Get the source object for the resource.
   *
   * @throws \Exception
   *   If an appropriate model class cannot be found.
   *
   * @return \Platformsh\Client\Model\Resource
   *   An instance of one of the model classes extending the Resource class, for
   *   example \Platformsh\Client\Model\Subscription.
   */
  public function source() {
    if (!isset($this->source)) {
      $client = platformsh_api_client();
      $className = '\\Platformsh\\Client\\Model\\' . $this->type;
      if (!class_exists($className)) {
        throw new \Exception("Model class not found: $className");
      }
      $this->source = new $className($this->data, $this->url, $client->getConnector()->getClient());
    }

    return $this->source;
  }
}
