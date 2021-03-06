@api
Feature: sharing
	Background:
		Given using API version "1"
		And using old DAV path

	Scenario: Downloading from upload-only share is forbidden
		Given user "user0" has been created
		And user "user0" has moved file "/textfile0.txt" to "/FOLDER/test.txt"
		When user "user0" creates a share using the API with settings
			| path        | FOLDER |
			| shareType   | 3      |
			| permissions | 4      |
		Then the public shared file "test.txt" should not be able to be downloaded
		And the HTTP status code should be "404"

	Scenario: Share a file by multiple channels and download from sub-folder and direct file share
		Given user "user0" has been created
		And user "user1" has been created
		And user "user2" has been created
		And group "group0" has been created
		And user "user1" has been added to group "group0"
		And user "user2" has been added to group "group0"
		And user "user0" has created a folder "/common"
		And user "user0" has created a folder "/common/sub"
		And user "user0" has shared folder "common" with group "group0"
		And user "user1" has shared file "textfile0.txt" with user "user2"
		And user "user1" has moved file "/textfile0.txt" to "/common/textfile0.txt"
		And user "user1" has moved file "/common/textfile0.txt" to "/common/sub/textfile0.txt"
		When user "user2" uploads file "data/file_to_overwrite.txt" to "/textfile0 (2).txt" using the API
		And user "user2" downloads file "/common/sub/textfile0.txt" with range "bytes=0-8" using the API
		Then the downloaded content should be "BLABLABLA"
		And the downloaded content when downloading file "/textfile0 (2).txt" for user "user2" with range "bytes=0-8" should be "BLABLABLA"
		And user "user2" should see the following elements
			| /common/sub/textfile0.txt |
			| /textfile0%20(2).txt      |

	Scenario: Download a file that is in a folder contained in a folder that has been shared with a user with default permissions
		Given user "user0" has been created
		And user "user1" has been created
		And user "user0" creates a share using the API with settings
			| path      | PARENT     |
			| shareType | 0          |
			| shareWith | user1      |
		Then the user "user1" should be able to download the file "/PARENT (2)/CHILD/child.txt" using the API

	Scenario: Download a file that is in a folder contained in a folder that has been shared with a group with default permissions
		Given user "user0" has been created
		And user "user1" has been created
		And group "sharegroup" has been created
		And user "user1" has been added to group "sharegroup"
		And user "user0" has shared folder "PARENT" with group "sharegroup"
		Then the user "user1" should be able to download the file "/PARENT (2)/CHILD/child.txt" using the API

	Scenario: Download a file that is in a folder contained in a folder that has been shared with public with default permissions
		Given user "user0" has been created
		When user "user0" creates a share using the API with settings
			| path         | PARENT   |
			| shareType    | 3        |
			| password     | publicpw |
		Then the public should be able to download the range "bytes=1-7" of file "/CHILD/child.txt" from inside the last public shared folder with password "publicpw" and the content should be "wnCloud"

	Scenario: Download a file that is in a folder contained in a folder that has been shared with a user with Read/Write permission 
		Given user "user0" has been created
		And user "user1" has been created
		When user "user0" creates a share using the API with settings
			| path        | PARENT |
			| shareType   | 0      |
			| shareWith   | user1  |
			| permissions | 15     |
		Then the user "user1" should be able to download the file "/PARENT (2)/CHILD/child.txt" using the API

	Scenario: Download a file that is in a folder contained in a folder that has been shared with a group with Read/Write permission 
		Given user "user0" has been created
		And user "user1" has been created
		And group "sharegroup" has been created
		And user "user1" has been added to group "sharegroup"
		When user "user0" creates a share using the API with settings
			| path        | PARENT      |
			| shareType   | 1           |
			| shareWith   | sharegroup  |
			| permissions | 15          |
		Then the user "user1" should be able to download the file "/PARENT (2)/CHILD/child.txt" using the API

	Scenario: Download a file that is in a folder contained in a folder that has been shared with public with Read/Write permission 
		Given user "user0" has been created
		When user "user0" creates a share using the API with settings
			| path         | PARENT   |
			| shareType    | 3        |
			| password     | publicpw |
			| permissions | 15        |
		Then the public should be able to download the range "bytes=1-7" of file "/CHILD/child.txt" from inside the last public shared folder with password "publicpw" and the content should be "wnCloud"

	Scenario: Download a file that is in a folder contained in a folder that has been shared with a user with Read only permission 
		Given user "user0" has been created
		And user "user1" has been created
		When user "user0" creates a share using the API with settings
			| path        | PARENT |
			| shareType   | 0      |
			| shareWith   | user1  |
			| permissions | 1     |
		Then the user "user1" should be able to download the file "/PARENT (2)/CHILD/child.txt" using the API

	Scenario: Download a file that is in a folder contained in a folder that has been shared with a group with Read only permission 
		Given user "user0" has been created
		And user "user1" has been created
		And group "sharegroup" has been created
		And user "user1" has been added to group "sharegroup"
		When user "user0" creates a share using the API with settings
			| path        | PARENT      |
			| shareType   | 1           |
			| shareWith   | sharegroup  |
			| permissions | 1          |
		Then the user "user1" should be able to download the file "/PARENT (2)/CHILD/child.txt" using the API

	Scenario: Download a file that is in a folder contained in a folder that has been shared with public with Read only permission 
		Given user "user0" has been created
		When user "user0" creates a share using the API with settings
			| path         | PARENT   |
			| shareType    | 3        |
			| password     | publicpw |
			| permissions | 1        |
		Then the public should be able to download the range "bytes=1-7" of file "/CHILD/child.txt" from inside the last public shared folder with password "publicpw" and the content should be "wnCloud"