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
    Then I should see "Learn the basics" in the ".fct-hero" "css_element"
    And I should not see "Secure coding" in the ".fct-hero" "css_element"
    And I should see "Start the course" in the ".fct-hero" "css_element"
    And I should see "2 activities" in the ".fct-tile:nth-child(2)" "css_element"
    And I should see "Not started" in the ".fct-tile:nth-child(2)" "css_element"
    And ".fct-tiles" "css_element" should exist

  @javascript
  Scenario: Progress and the continue button follow the completion of activities
    Given I am on the "Page A" "page activity" page logged in as "student1"
    And I toggle the manual completion state of "Page A"
    When I am on the "C1" "course" page
    Then I should see "1 of 2 completed" in the ".fct-tile:nth-child(2)" "css_element"
    And I should see "In progress" in the ".fct-tile:nth-child(2)" "css_element"
    And I should see "Continue where you left off" in the ".fct-hero" "css_element"

  Scenario: The course map lists every section and activity
    When I am on the "C1" "course" page logged in as "student1"
    And I follow "Course map"
    Then I should see "Page A"
    And I should see "Page C"
    And I should see "Not started"

  Scenario: The skip link and the context line are present for students
    When I am on the "Page A" "page activity" page logged in as "student1"
    Then I should see "Activity 1 of 2"
    And "Skip to content" "link" should exist

  Scenario: Teachers editing the course get the standard editing interface
    When I am on the "C1" "course" page logged in as "teacher1"
    And I turn editing mode on
    Then I should see "Add content"
    And ".fct-hero" "css_element" should exist
    And ".fct-tiles" "css_element" should not exist

  Scenario: Opening a section shows its header, its activities and the way to the next one
    Given I am on the "C1" "course" page logged in as "student1"
    When I click on "New section" "link" in the ".fct-tile:nth-child(2)" "css_element"
    Then I should see "Section 1 of 3" in the ".fct-ctxbar" "css_element"
    And I should see "2 activities" in the ".fct-sechead" "css_element"
    And I should see "Page A"
    And I should see "Next section" in the ".fct-secnav" "css_element"
    And I should see "General" in the ".fct-secnav" "css_element"
    And I click on "Next section" "link"
    And I should see "Section 2 of 3" in the ".fct-ctxbar" "css_element"
    And I should see "Page C"

  Scenario: An empty section says so
    Given I am on the "C1" "course" page logged in as "student1"
    When I click on "New section" "link" in the ".fct-tile:nth-child(4)" "css_element"
    Then I should see "This section has no activities yet."

  Scenario: The course name can be repeated in the header when the theme hides it
    Given I am on the "C1" "course editing" page logged in as "teacher1"
    And I set the field "Repeat the course name in the header" to "1"
    And I press "Save and display"
    Then I should see "Secure coding" in the ".fct-hero" "css_element"

  @javascript
  Scenario: The course map opens as a panel without leaving the page
    Given I am on the "C1" "course" page logged in as "student1"
    When I click on "Course map" "link"
    Then "dialog.fct-sheet" "css_element" should be visible
    And I should see "Page C" in the "dialog.fct-sheet" "css_element"
    And I click on "Close the course map" "button"
    And "dialog.fct-sheet" "css_element" should not be visible
