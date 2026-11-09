@format @format_customtiles
Feature: Custom tiles course page
  In order to see my course in a visual way
  As a student
  I need a header with my progress and a grid of sections

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname      | shortname | format       | numsections | enablecompletion | summary          |
      | Secure coding | C1        | customtiles  | 3           | 1                | Learn the basics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name   | course | section | completion |
      | page     | Page A | C1     | 1       | 1          |
      | page     | Page B | C1     | 1       | 1          |
      | page     | Page C | C1     | 2       | 1          |

  Scenario: A student sees the hero, the tiles and the start button
    When I am on the "C1" "course" page logged in as "student1"
    Then I should see "Secure coding" in the ".ct-hero" "css_element"
    And I should see "Learn the basics" in the ".ct-hero" "css_element"
    And I should see "Start the course" in the ".ct-hero" "css_element"
    And I should see "2 activities" in the ".ct-tile:nth-child(2)" "css_element"
    And I should see "New" in the ".ct-tile:nth-child(2)" "css_element"
    And ".ct-tiles" "css_element" should exist

  @javascript
  Scenario: Progress and the continue button follow the completion of activities
    Given I am on the "Page A" "page activity" page logged in as "student1"
    And I toggle the manual completion state of "Page A"
    When I am on the "C1" "course" page
    Then I should see "1 of 2 completed" in the ".ct-tile:nth-child(2)" "css_element"
    And I should see "In progress" in the ".ct-tile:nth-child(2)" "css_element"
    And I should see "Continue where you left off" in the ".ct-hero" "css_element"

  Scenario: The course map lists every section and activity
    When I am on the "C1" "course" page logged in as "student1"
    And I follow "Course map"
    Then I should see "Page A"
    And I should see "Page C"
    And I should see "New"

  Scenario: The skip link and the context line are present for students
    When I am on the "Page A" "page activity" page logged in as "student1"
    Then I should see "Activity 1 of 2"
    And "Skip to content" "link" should exist

  Scenario: Teachers editing the course get the standard editing interface
    When I am on the "C1" "course" page logged in as "teacher1"
    And I turn editing mode on
    Then I should see "Add content"
    And ".ct-hero" "css_element" should exist
    And ".ct-tiles" "css_element" should not exist
