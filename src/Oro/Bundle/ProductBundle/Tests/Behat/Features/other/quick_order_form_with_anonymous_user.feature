@regression
@ticket-BB-20530
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Quick order form with anonymous user
  Make sure the anonymous user does not see the 404 code on Quick order form page and is redirected to sign in page.

  Scenario: Check quick order form from anonymous user without permissions
    Given I am on "/customer/product/quick-add"
    Then I should not see "404 Not Found"
    And should see "Log In"

  Scenario: Check redirect to Quick Order Form page after login
    Given I fill "Customer Login Form" with:
      | Email    | AmandaRCole@example.org |
      | Password | AmandaRCole@example.org |
    And click "Log In"
    Then I should see "Amanda Cole"
    And should see "Quick Order"
