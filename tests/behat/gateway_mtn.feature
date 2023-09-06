@paygw @paygw_mtnafrica
Feature: MTN Africa payment gateway

  In order to control student access to courses
  I need to be able to add an MTN Africa payment gateway

  Background:
    Given the following "users" exist:
      | username | phone2      | country |
      | student1 | 46733123454 | UG      |
      | student2 | 46733123999 | UG      |
      | manager1 | 46733123452 | UG      |
    And the following "categories" exist:
      | name         | idnumber |
      | paid courses | payment  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | payment  |
      | Course 2 | C2        | payment  |
    And the following "activities" exist:
      | activity | name     | course | idnumber |
      | page     | TestPage | C1     | page1    |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | manager1 | C1     | manager |
      | manager1 | C2     | manager |
    And the following "core_payment > payment accounts" exist:
      | name     | gateways  |
      | Account1 | mtnafrica |
    And I log in as "admin"
    And I configure mtn
    And I add "Enrolment on payment" enrolment method in "Course 1" with:
      | Payment account | Account1 |
      | Enrolment fee   | 50       |
      | Currency        | Euro     |
    And I add "Enrolment on payment" enrolment method in "Course 2" with:
      | Payment account | Account1        |
      | Enrolment fee   | 5000            |
      | Currency        | Congolese Franc |
    And I log out

  @javascript
  Scenario: Student can cancel MTN Africa payment
    Given I log in as "student1"
    And I am on course index
    And I follow "paid courses"
    When I follow "Course 1"
    Then I should see "This course requires a payment for entry."
    And I press "Select payment type"
    And I should see "MTN Africa" in the "Select payment type" "dialogue"
    And I click on "Proceed" "button" in the "Select payment type" "dialogue"
    And I click on "Close" "button" in the "MTN Africa" "dialogue"
    And I should see "MTN Africa" in the "Select payment type" "dialogue"

  @javascript
  Scenario: Student can see the MTN Africa payment prompt on the course enrolment page
    Given I log in as "student1"
    And I am on course index
    And I follow "paid courses"
    When I follow "Course 1"
    Then I should see "This course requires a payment for entry."
    And I should see "50"
    And I press "Select payment type"
    And I should see "MTN Africa" in the "Select payment type" "dialogue"
    And I should see "50"
    And I click on "Proceed" "button" in the "Select payment type" "dialogue"
    And I should see "46733123"
    And I click on "Proceed" "button" in the "MTN Africa" "dialogue"
    And I wait "5" seconds
    And I click on "Cancel" "button" in the "MTN Africa" "dialogue"
    # And I get a timeout

  @javascript
  Scenario: Student is enrolled in course after an MTN Africa payment
    Given I log in as "student2"
    And I am on course index
    And I follow "paid courses"
    When I follow "Course 1"
    And I should see "This course requires a payment for entry."
    And I should see "50"
    And I press "Select payment type"
    And I should see "MTN Africa" in the "Select payment type" "dialogue"
    And I should see "50"
    And I click on "Proceed" "button" in the "Select payment type" "dialogue"
    And I should see "46733123999"
    And I should see "profile page"
    And I wait until the page is ready
    And I click on "Proceed" "button" in the "MTN Africa" "dialogue"
    And I wait until the page is ready
    And I click on "Proceed" "button" in the "MTN Africa" "dialogue"
    And I wait until the page is ready
    # We are in.
    Then I should see "Course 1"
    And I should see "TestPage"

  @javascript
  Scenario: Student should be logged in automatically after an MTN Africa payment
    Given I log in as "student2"
    And I am on course index
    And I follow "paid courses"
    When I follow "Course 1"
    Then I should see "This course requires a payment for entry."
    And I should see "50"
    And I press "Select payment type"
    And I should see "MTN Africa" in the "Select payment type" "dialogue"
    And I should see "50"
    And I click on "Proceed" "button" in the "Select payment type" "dialogue"
    And I should see "46733123"
    And I wait until the page is ready
    And I click on "Proceed" "button" in the "MTN Africa" "dialogue"
    And I wait until the page is ready
    And I click on "Proceed" "button" in the "MTN Africa" "dialogue"
    # We are in.
    And I should see "TestPage"

  Scenario: Guest can see the login prompt on the MTN Africa course enrolment page with round price
    Given I log in as "guest"
    And I am on course index
    And I follow "paid courses"
    When I follow "Course 1"
    Then I should see "This course requires a payment for entry."
    And I should see "50"
    And I should see "Log in to the site"

  Scenario: Guest can see the login prompt on the MTN Africa course enrolment page
    Given I log in as "guest"
    And I am on course index
    And I follow "paid courses"
    When I follow "Course 2"
    Then I should see "This course requires a payment for entry."
    And I should see "5,000.00"
    And I should see "Log in to the site"
