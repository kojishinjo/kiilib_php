<?php
require_once (dirname(__FILE__) . '/../../src/KiiGroup.php');
require_once (dirname(__FILE__) . '/../../src/kii/KiiTopicAPI.php');
require_once (dirname(__FILE__) . '/../mock/MockClientFactory.php');

class TestKiiTopicAPI extends PHPUnit_Framework_TestCase{
	private $APP_ID = 'appId';
	private $APP_KEY = 'appKey';

	private $factory;
	private $context;

	public function __construct() {
		$this->factory = new MockClientFactory();

		$this->context = new KiiContext($this->APP_ID, $this->APP_KEY, KiiContext::SITE_US);
		$this->context->setClientFactory($this->factory);		
	}

	public function test_0000_sendMessage_ok() {
		$c = $this->context;
		$api = new KiiTopicAPI($c);

		$groupId = 'group1234';
		$topicName = 'myTopic';
		$topic = new KiiTopic(new KiiGroup($groupId), $topicName);
		
		$message = new KiiTopicMessage();
		$message->data['msg'] = 'hello';
		
		// set mock
		$respBody = '{'. 
			'"pushMessageID":"c91d5a3b-3ae1-45ea-9f2f-1265ddd080ce"'.
			'}';
		$this->factory->newClient()->
			addToSend(new MockResponse(201, $respBody));
		$result = $api->sendMessage($topic, $message);
		
		// assertion
		$this->assertEquals('c91d5a3b-3ae1-45ea-9f2f-1265ddd080ce',
							$result);
		$this->assertEquals('https://api.kii.com/api/apps/appId/groups/group1234/topics/myTopic/push/messages',
							$this->factory->newClient()->urlArgs[0]);
	}

	public function test_0010_sendMessage_cloud_exception() {
		$c = $this->context;
		$api = new KiiTopicAPI($c);

		$groupId = 'group1234';
		$topicName = 'myTopic';
		$topic = new KiiTopic(new KiiGroup($groupId), $topicName);
		
		$message = new KiiTopicMessage();
		$message->data['msg'] = 'hello';
		
		// set mock
		$respBody = '{'.
			'"errorCode":"TOPIC_NOT_FOUND",'.
			'"message":"The topic was not found",'.
			'"objectScope":{'.
            '"appID":"1a460467",'.
            '"groupID":"group1234",'.
            '"type":"APP_AND_GROUP"},'.
			'"topicID":"myTopic",'.
			'"suppressed": [ ]}';
		$this->factory->newClient()->
			addToSend(new MockResponse(404, $respBody));
		try {
			$result = $api->sendMessage($topic, $message);
			$this->assertFail('Exception must be thrown');
		} catch (CloudException $e) {
			$this->assertEquals(404, $e->getStatus());
		}
	}
	
}
?>