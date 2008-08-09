<?php
/**
 * 
 * @todo Test Relation getters
 * @todo Test filter and limit through GET params
 * @todo Test DELETE verb
 *
 */
class RestfulServerTest extends SapphireTest {
	
	static $fixture_file = 'cms/tests/RestfulServerTest.yml';
	
	public function testApiAccess() {
		// normal GET should succeed with $api_access enabled
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$response = Director::test($url, null, null, 'GET');
		$this->assertEquals($response->getStatusCode(), 200);
		
		$_SERVER['PHP_AUTH_USER'] = 'user@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'user';
		
		// even with logged in user a GET with $api_access disabled should fail
		$url = "/api/v1/RestfulServerTest_Page/1";
		$response = Director::test($url, null, null, 'GET');
		$this->assertEquals($response->getStatusCode(), 403);
	}
	
	/*
	public function testAuthenticatedGET() {
		// @todo create additional mock object with authenticated VIEW permissions
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$response = Director::test($url, null, null, 'GET');
		$this->assertEquals($response->getStatusCode(), 403);
		
		$_SERVER['PHP_AUTH_USER'] = 'user@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'user';
		
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$response = Director::test($url, null, null, 'GET');
		$this->assertEquals($response->getStatusCode(), 200);
	}
	*/

	public function testAuthenticatedPUT() {
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$data = array('Comment' => 'created');
		
		$response = Director::test($url, $data, null, 'PUT');
		$this->assertEquals($response->getStatusCode(), 403); // Permission failure
		
		$_SERVER['PHP_AUTH_USER'] = 'editor@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'editor';
		$response = Director::test($url, $data, null, 'PUT');
		$this->assertEquals($response->getStatusCode(), 200); // Success
	}

	public function testPUTWithFormEncoded() {
		$_SERVER['PHP_AUTH_USER'] = 'editor@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'editor';
		
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$data = array('Comment' => 'updated');
		$response = Director::test($url, $data, null, 'PUT');
		$this->assertEquals($response->getStatusCode(), 200); // Success
		// Assumption: XML is default output
		$responseArr = Convert::xml2array($response->getBody());
		$this->assertEquals($responseArr['ID'], 1);
		$this->assertEquals($responseArr['Comment'], 'updated');
	}

	public function testPOSTWithFormEncoded() {
		$_SERVER['PHP_AUTH_USER'] = 'editor@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'editor';
		
		$url = "/api/v1/RestfulServerTest_Comment";
		$data = array('Comment' => 'created');
		$response = Director::test($url, $data, null, 'POST');
		$this->assertEquals($response->getStatusCode(), 201); // Created
		// Assumption: XML is default output
		$responseArr = Convert::xml2array($response->getBody());
		$this->assertEquals($responseArr['ID'], 2);
		$this->assertEquals($responseArr['Comment'], 'created');
	}
	
	public function testPUTwithJSON() {
		$_SERVER['PHP_AUTH_USER'] = 'editor@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'editor';
		
		// by mimetype
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$body = '{"Comment":"updated"}';
		$response = Director::test($url, null, null, 'PUT', $body, array('Content-Type'=>'application/json'));
		$this->assertEquals($response->getStatusCode(), 200); // Updated
		$obj = Convert::json2obj($response->getBody());
		$this->assertEquals($obj->ID, 1);
		$this->assertEquals($obj->Comment, 'updated');

		// by extension
		$url = "/api/v1/RestfulServerTest_Comment/1.json";
		$body = '{"Comment":"updated"}';
		$response = Director::test($url, null, null, 'PUT', $body);
		$this->assertEquals($response->getStatusCode(), 200); // Updated
		$obj = Convert::json2obj($response->getBody());
		$this->assertEquals($obj->ID, 1);
		$this->assertEquals($obj->Comment, 'updated');
	}
	
	public function testPUTwithXML() {
		$_SERVER['PHP_AUTH_USER'] = 'editor@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'editor';
		
		// by mimetype
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$body = '<RestfulServerTest_Comment><Comment>updated</Comment></RestfulServerTest_Comment>';
		$response = Director::test($url, null, null, 'PUT', $body, array('Content-Type'=>'text/xml'));
		$this->assertEquals($response->getStatusCode(), 200); // Updated
		$obj = Convert::xml2array($response->getBody());
		$this->assertEquals($obj['ID'], 1);
		$this->assertEquals($obj['Comment'], 'updated');

		// by extension
		$url = "/api/v1/RestfulServerTest_Comment/1.xml";
		$body = '<RestfulServerTest_Comment><Comment>updated</Comment></RestfulServerTest_Comment>';
		$response = Director::test($url, null, null, 'PUT', $body);
		$this->assertEquals($response->getStatusCode(), 200); // Updated
		$obj = Convert::xml2array($response->getBody());
		$this->assertEquals($obj['ID'], 1);
		$this->assertEquals($obj['Comment'], 'updated');
	}
	
	public function testHTTPAcceptAndContentType() {
		$url = "/api/v1/RestfulServerTest_Comment/1";
		
		$headers = array('Accept' => 'application/json');
		$response = Director::test($url, null, null, 'GET', null, $headers);
		$this->assertEquals($response->getStatusCode(), 200); // Success
		$obj = Convert::json2obj($response->getBody());
		$this->assertEquals($obj->ID, 1);
		$this->assertEquals($response->getHeader('Content-Type'), 'application/json');
	}

	public function testNotFound(){
		$_SERVER['PHP_AUTH_USER'] = 'user@test.com';
		$_SERVER['PHP_AUTH_PW'] = 'user';
		
		$url = "/api/v1/RestfulServerTest_Comment/99";
		$response = Director::test($url, null, null, 'GET');
		$this->assertEquals($response->getStatusCode(), 404);
	}
	
	public function testMethodNotAllowed() {
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$response = Director::test($url, null, null, 'UNKNOWNHTTPMETHOD');
		$this->assertEquals($response->getStatusCode(), 405);
	}
	
	public function testUnsupportedMediaType() {
		$url = "/api/v1/RestfulServerTest_Comment/1";
		$data = "Comment||\/||updated"; // weird format
		$headers = array('Content-Type' => 'text/weirdformat');
		$response = Director::test($url, null, null, 'POST', $data, $headers);
		$this->assertEquals($response->getStatusCode(), 415);
	}
	
}

/**
 * Everybody can view comments, logged in members in the "users" group can create comments,
 * but only "editors" can edit or delete them.
 *
 */
class RestfulServerTest_Comment extends DataObject implements PermissionProvider,TestOnly {
	
	static $api_access = true;
	
	static $db = array(
		"Name" => "Varchar(255)",
		"Comment" => "Text"
	);
	
	public function providePermissions(){
		return array(
			'EDIT_Comment' => 'Edit Comment Objects',
			'CREATE_Comment' => 'Create Comment Objects',
			'DELETE_Comment' => 'Delete Comment Objects',
		);
	}
	
	public function canView($member = null) {
		return true;
	}
	
	public function canEdit($member = null) {
		return Permission::checkMember($member, 'EDIT_Comment');
	}
	
	public function canDelete($member = null) {
		return Permission::checkMember($member, 'DELETE_Comment');
	}
	
	public function canCreate($member = null) {
		return Permission::checkMember($member, 'CREATE_Comment');
	}
	
}

class RestfulServerTest_Page extends DataObject implements TestOnly {
	
	static $api_access = false;
	
	static $db = array(
		'Title' => 'Text',	
		'Content' => 'HTMLText',
	);
}
?>