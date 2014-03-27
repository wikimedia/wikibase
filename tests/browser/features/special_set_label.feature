# Wikidata UI tests
#
# Author:: Thiemo Mättig (thiemo.maettig@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for the Special:SetLabel page tests

@wikidata.beta.wmflabs.org @special_pages
Feature: Special:SetLabel page

  @ui_only
  Scenario: Special:SetLabel page has all required elements
    Given I am on the Special:SetLabel page
    Then ID input field should be there
      And Language input field should be there
      And Label input field should be there
      And Set label button should be there

  @ui_only
  Scenario: Logged in user does not get warning
    Given I am logged in to the repo
      And I am on the Special:SetLabel page
    Then Anonymous edit warning should not be there

  @ui_only
  Scenario: Anonymous user gets warning
    Given I am not logged in to the repo
      And I am on the Special:SetLabel page
    Then Anonymous edit warning should be there

  Scenario: Add a label
    Given I have the following empty items:
        | item1 |
      And I am on the Special:SetLabel page
      And I enter the ID of item item1 into the ID input field
      And I enter en into the language input field
      And I enter Something into the label input field
      And I press the set label button
      And I am on the page of item item1
    Then Something should be displayed as label

  Scenario: Edit an existing label
    Given I have the following items:
        | item1 |
      And I am on the Special:SetLabel page
      And I enter the ID of item item1 into the ID input field
      And I enter en into the language input field
      And I enter Something different into the label input field
      And I press the set label button
      And I am on the page of item item1
    Then Something different should be displayed as label

  Scenario: Edit using an invalid language fails
    Given I have the following items:
        | item1 |
      And I am on the Special:SetLabel page
      And I enter the ID of item item1 into the ID input field
      And I enter Something invalid into the language input field
      And I enter Something new into the label input field
      And I press the set label button
      And I am on the page of item item1
    Then Original label of item item1 should be displayed

  Scenario: Edit using an invalid ID fails
    When I am on the Special:SetLabel page
      And I enter something invalid in the ID input field
      And I enter en into the language input field
      And I enter Something new into the label input field
      And I press the set label button
    Then An error message should be displayed on the special page
