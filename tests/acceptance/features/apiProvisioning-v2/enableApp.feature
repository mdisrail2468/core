@api
Feature: enable an app
As an admin
I want to be able to enable a disabled app
So that I can use the app features again

	Background:
		Given using API version "2"

	Scenario: Admin enables an app
		Given the app "comments" has been disabled
		When the administrator enables the app "comments"
		Then the OCS status code should be "200"
		And the HTTP status code should be "200"
		And app "comments" should be enabled

	@skip @issue-31276
	Scenario: subadmin tries to enable an app
		Given user "subadmin" has been created
		And group "newgroup" has been created
		And user "subadmin" has been made a subadmin of group "newgroup"
		And the app "comments" has been disabled
		When user "subadmin" enables the app "comments"
		Then the OCS status code should be "997"
		And the HTTP status code should be "401"
		And app "comments" should be disabled

	@skip @issue-31276
	Scenario: normal user tries to enable an app
		Given user "newuser" has been created
		And the app "comments" has been disabled
		When user "newuser" enables the app "comments"
		Then the OCS status code should be "997"
		And the HTTP status code should be "401"
		And app "comments" should be disabled