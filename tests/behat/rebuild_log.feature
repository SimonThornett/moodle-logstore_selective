@logstore @logstore_selective
Feature: Rebuild the selective log from the standard log
  In order to backfill the selective log after enabling events
  As an admin
  I need to be able to queue a rebuild of the selective log

  Scenario: Queue a rebuild from the settings page
    Given the following config values are set as admin:
      | config         | value                                 | plugin  |
      | enabled_stores | logstore_standard,logstore_selective | tool_log |
    And I log in as "admin"
    And I navigate to "Plugins > Logging > Selective log" in site administration
    When I set the field "Rebuild selective log" to "1"
    And I press "Save changes"
    Then I should see "The selective log rebuild has been queued and will run on the next cron run."
    And the field "Rebuild selective log" matches value "0"
    And I set the field "Rebuild selective log" to "1"
    And I press "Save changes"
    And I should see "A selective log rebuild is already queued and will run on the next cron run."
    And I run all adhoc tasks
    And I set the field "Rebuild selective log" to "1"
    And I press "Save changes"
    And I should see "The selective log rebuild has been queued and will run on the next cron run."
