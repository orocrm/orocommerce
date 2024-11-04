@regression
@ticket-BB-9989
@fixture-OroProductBundle:ProductAttributesFixture.yml
Feature: Product attribute html
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add product attribute and have attribute data in search and filter

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create product attribute
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | WYSIWYGField |
      | Type       | WYSIWYG      |
    And I click "Continue"
    Then I should see that "Product Attribute Storefront Options" contains "Searchable"
    And I should see that "Product Attribute Storefront Options" contains "Filterable"
    And I should see that "Product Attribute Storefront Options" does not contain "Sortable"

    When I fill form with:
      | Searchable | Yes |
      | Filterable | Yes |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message
    And I should not see "Update schema"

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [WYSIWYGField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill in WYSIWYG "Product WYSIWYGField Attribute Content" with "TestDaTa<br><p>NewLiNe</p>"
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product grid search
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I type "TestDaTa NewLiNe" in "search"
    And I click "Search Button"
    Then I should see "SKU123" product
    And I should not see "SKU456" product

  Scenario: Check product grid filter and sorter
    Given I click "NewCategory" in hamburger menu
    And I should see "SKU123" product
    And I should see "SKU456" product
    When I filter WYSIWYGField as contains "TestDaTa NewLiNe"
    Then I should see "SKU123" product
    And I should not see "SKU456" product

  Scenario: Delete product attribute
    Given I proceed as the Admin
    When I go to Products/ Product Attributes
    And I click "Remove" on row "WYSIWYGField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    When I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should not see "Update schema"
