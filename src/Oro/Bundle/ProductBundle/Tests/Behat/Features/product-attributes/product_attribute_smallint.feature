@regression
@ticket-BB-9989
@fixture-OroProductBundle:ProductAttributesFixture.yml
Feature: Product attribute smallint
  In order to have custom attributes for Product entity
  As an Administrator
  I need to be able to add product attribute and have attribute data in search, filter and sorter

  Scenario: Create product attribute
    Given I login as administrator
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | SmallIntField |
      | Type       | SmallInt      |
    And I click "Continue"
    Then I should see that "Product Attribute Storefront Options" does not contain "Searchable"
    And I should see that "Product Attribute Storefront Options" contains "Filterable"
    And I should see that "Product Attribute Storefront Options" contains "Sortable"

    When I fill form with:
      | Filterable | Yes |
      | Sortable   | Yes |
    And I save and close form
    Then I should see "Attribute was successfully saved" flash message
    And I should not see "Update schema"

    When I check "SmallInt" in "Data Type" filter
    Then I should see following grid:
      | Name          | Storage type     |
      | SmallIntField | Serialized field |

  Scenario: Update product family with new attribute
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | Attributes | [SmallIntField] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Update product
    Given I go to Products/ Products
    When I click "Edit" on row "SKU123" in grid
    And I fill "Product Form" with:
      | SmallIntField | 32768 |
    And I save form
    Then I should see validation errors:
      | SmallIntField | This value should be between -32,768 and 32,767. |
    When I fill "Product Form" with:
      | SmallIntField | -32769 |
    And I save form
    Then I should see validation errors:
      | SmallIntField | This value should be between -32,768 and 32,767. |
    When I fill "Product Form" with:
      | SmallIntField | 32767 |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check product grid search
    Given I login as AmandaRCole@example.org buyer
    When I type "32167" in "search"
    And I click "Search Button"
    Then I should not see "SKU123" product
    And I should not see "SKU456" product

  Scenario: Check product grid filter and sorter
    Given I click "NewCategory" in hamburger menu
    And I should see "SKU123" product
    And I should see "SKU456" product
    When I filter SmallIntField as equals "32767"
    Then I should see "SKU123" product
    And I should not see "SKU456" product
    And grid sorter should have "SmallIntField" options

  Scenario: Delete product attribute
    Given I login as administrator
    Given I go to Products/ Product Attributes
    When I click Remove "SmallIntField" in grid
    Then I should see "Are you sure you want to delete this attribute?"
    And I click "Yes"
    Then I should see "Attribute successfully deleted" flash message
    And I should not see "Update schema"
