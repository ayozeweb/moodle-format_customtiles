@format @format_customtiles
Feature: Personalise the sections of a Custom tiles course
  In order to make the course attractive
  As a teacher
  I need to set a subtitle, an icon, a colour and the estimated time of a section

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname      | shortname | format      | numsections |
      | Secure coding | C1        | customtiles | 2           |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: The section options are shown on the tile
    Given I am on the "C1" "course" page logged in as "teacher1"
    And I turn editing mode on
    And I edit the section "1"
    When I set the following fields to these values:
      | Subtitle                  | First steps |
      | Icon                      | Flask       |
      | Colour                    | #0e7490     |
      | Estimated time (minutes)  | 90          |
    And I press "Save changes"
    And I log out
    And I am on the "C1" "course" page logged in as "student1"
    Then I should see "First steps" in the ".ct-tile:nth-child(2)" "css_element"
    And I should see "1 h 30 min" in the ".ct-tile:nth-child(2)" "css_element"
    And I should see "Duration: 1 h 30 min" in the ".ct-hero" "css_element"

  Scenario: A colour without enough contrast is accepted and corrected on the course page
    Given I am on the "C1" "course editing" page logged in as "teacher1"
    When I set the following fields to these values:
      | Visual theme  | Light   |
      | Accent colour | #ffff00 |
    And I press "Save and display"
    Then I should see "The colour was adjusted to reach AA contrast."
