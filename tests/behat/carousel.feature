@format @format_customtiles @javascript
Feature: Carousel layout
  In order to browse the sections on a phone
  As a student
  I need a carousel with buttons that work without a mouse swipe

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname      | shortname | format      | numsections | layout   |
      | Secure coding | C1        | customtiles | 4           | carousel |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |

  Scenario: The carousel buttons move between sections
    Given I change window size to "mobile"
    And I am on the "C1" "course" page logged in as "student1"
    Then the "aria-current" attribute of ".ct-carousel__dot:first-child" "css_element" should contain "true"
    And the "disabled" attribute of "[data-action='prev']" "css_element" should be set
    When I click on "Next section" "button"
    Then the "aria-current" attribute of ".ct-carousel__dot" "css_element" should be set
    And the "disabled" attribute of "[data-action='prev']" "css_element" should not be set
