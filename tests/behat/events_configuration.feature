@logstore @logstore_selective @javascript
Feature: Configure which events are stored in the selective log
  In order to only log the events I need
  As an admin
  I need to filter, enable and save event settings

  Background:
    Given I log in as "admin"
    And I visit "/admin/tool/log/store/selective/index.php"

  Scenario: Filter the events list by search text
    When I set the field "Search events" to "User has logged in"
    Then "User has logged in" "table_row" should be visible
    And "Course viewed" "table_row" should not be visible
    And I click on "Clear filters" "button"
    And "Course viewed" "table_row" should be visible

  Scenario: Enable an event and save the settings
    Given I set the field "Search events" to "User has logged in"
    When I click on "Log User has logged in" "checkbox"
    Then I should see "Unsaved changes: 1"
    And I set the field "Log duration for User has logged in" to "35 days"
    And I should see "Unsaved changes: 2"
    And I press "Save changes"
    And I should see "Settings have been updated."
    And I should see "Unsaved changes: 0"
    And I reload the page
    And the field "Log User has logged in" matches value "1"
    And the field "Log duration for User has logged in" matches value "35 days"

  Scenario: Bulk enable the filtered events and filter by status
    Given I set the field "Search events" to "logged"
    When I press "Enable"
    And I press "Save changes"
    And I should see "Settings have been updated."
    And I click on "Clear filters" "button"
    And I set the field "Status" to "Enabled"
    Then "User has logged in" "table_row" should be visible
    And "Course viewed" "table_row" should not be visible
    And I set the field "Status" to "Disabled"
    And "Course viewed" "table_row" should be visible
    And "User has logged in" "table_row" should not be visible

  Scenario: Discard unsaved changes
    Given I set the field "Search events" to "User has logged in"
    And I click on "Log User has logged in" "checkbox"
    And I should see "Unsaved changes: 1"
    When I press "Discard changes"
    Then I should see "Unsaved changes: 0"
    And the field "Log User has logged in" matches value "0"
