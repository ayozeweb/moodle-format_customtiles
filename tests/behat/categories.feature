@format @format_customtiles
Feature: Activity categories
  In order to tell the kind of each activity
  As an administrator and a teacher
  I need categories (such as the SEPE preset) shown as badges and a legend

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname      | shortname | format      | numsections |
      | Secure coding | C1        | customtiles | 1           |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name   | course | section |
      | page     | Page A | C1     | 1       |

  Scenario: The administrator loads the SEPE preset and adds a category
    Given I log in as "admin"
    And I am on site homepage
    And I visit "/course/format/customtiles/categories.php"
    When I press "Load the SEPE preset (AF, AE, PP, PO)"
    Then I should see "4 categories created."
    And I should see "Training activity"
    And I should see "Practical test"
    And I press "Load the SEPE preset (AF, AE, PP, PO)"
    And I should see "0 categories created."
    And I follow "Add category"
    And I set the following fields to these values:
      | Code   | XX          |
      | Name   | Extra       |
      | Colour | #334455     |
    And I press "Save changes"
    And I should see "Category saved."
    And I should see "Extra"

  Scenario: A category can be deleted only after confirmation
    Given I log in as "admin"
    And I visit "/course/format/customtiles/categories.php"
    And I press "Load the SEPE preset (AF, AE, PP, PO)"
    When I click on "Delete" "link" in the "PO" "table_row"
    Then I should see "Delete the category PO"
    And I press "Cancel"
    And I should see "Objective test"
    And I click on "Delete" "link" in the "PO" "table_row"
    And I press "Delete"
    And I should see "Category deleted."
    And I should not see "Objective test"

  Scenario: A teacher assigns a category and students see the badge and the legend
    Given I log in as "admin"
    And I visit "/course/format/customtiles/categories.php"
    And I press "Load the SEPE preset (AF, AE, PP, PO)"
    And I log out
    And I am on the "C1" "course" page logged in as "teacher1"
    And I follow "Activity categories"
    When I set the field "Page A" to "AF · Training activity"
    And I press "Save changes"
    Then I should see "Categories saved."
    And I log out
    And I am on the "Page A" "page activity" page logged in as "student1"
    And I am on the "C1" "course" page
    And I should see "Legend"
    And I should see "Training activity" in the ".fct-legend" "css_element"

  Scenario: Students are not offered the category assignment
    When I am on the "C1" "course" page logged in as "student1"
    Then I should not see "Activity categories"
