@regression
@ticket-BB-12111
@fixture-OroWebCatalogBundle:empty_web_catalog.yml
@fixture-OroWebCatalogBundle:subcategories-filter.yml
Feature: Sub-Categories filter
  In order to see products from multiple selected sub-categories at once
  As a customer user/visitor
  I want to see sub-categories of the current category as a multi-select filter

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Prepare Web Catalog
    Given I proceed as the Admin
    And I login as administrator
    And I set "Default Web Catalog" as default web catalog

    When I go to System/ Configuration
    And follow "Commerce/Catalog/Special Pages" on configuration sidebar
    And uncheck "Use default" for "Enable all products page" field
    Then I check "Enable all products page"
    And save form
    And I go to Marketing/Web Catalogs

    And I click "Edit Content Tree" on row "Default Web Catalog" in grid

    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Titles       | Root Node |
      | Landing Page | Homepage  |
    And I save form

    And I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Category"
    And I fill "Content Node Form" with:
      | Titles         | Lighting Products       |
      | Url Slug       | lighting-products       |
      | Sub-Categories | Include, show as filter |
    And I click "Lighting Products"
    And I save form

    And I click "Root Node"
    And I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Category"
    And I fill "Content Node Form" with:
      | Titles         | Medical Apparel |
      | Url Slug       | medical-apparel |
      | Sub-Categories | Do not include  |
    And I click "Medical Apparel"
    And I save form

    And I click "Root Node"
    And I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | Titles            | Products                                                     |
      | Url Slug          | products                                                     |
      | System Page Route | Oro Catalog Frontend Product Allproducts (All products page) |
    And I save form

  Scenario: No category page
    Given I proceed as the Buyer
    And I am on the homepage
    And I click "Products" in hamburger menu
    Then number of records in "Product Frontend Grid" should be 6
    And I should see "PSKU1" product
    And I should see "PSKU2" product
    And I should see "PSKU3" product
    And I should see "PSKU4" product
    And I should see "PSKU5" product
    And I should see "PSKU6" product
    When I click on "Frontend Grid Action Filter Button"
    Then I should not see an "Subcategories Filter" element

  Scenario: Category page for "Do not include"
    And I click "Medical Apparel" in hamburger menu
    Then number of records in "Product Frontend Grid" should be 1
    And I should see "PSKU5" product
    When I click on "Frontend Grid Action Filter Button"
    Then I should not see an "Subcategories Filter" element

  Scenario: Category page for "Include, show as filter"
    And I click "Lighting Products" in hamburger menu
    Then number of records in "Product Frontend Grid" should be 4
    And I should see "PSKU1" product
    And I should see "PSKU2" product
    And I should see "PSKU3" product
    And I should see "PSKU4" product
    When I click on "Frontend Grid Action Filter Button"
    Then I should see an "Subcategories Filter" element
    When I click on "Subcategories Filter"
    And I should see "Filter By Sub-Categories" filter with exact options in frontend product grid:
      | Architectural Floodlighting (1) |
      | Headlamps (2)                   |

  Scenario: Apply subcategories filter
    Given I check "Architectural Floodlighting (1)" in "Sub-Categories" filter in frontend product grid
    Then number of records in "Product Frontend Grid" should be 1
    And I should see "PSKU2" product
    When I check "Headlamps" in "Sub-Categories" filter in frontend product grid
    Then number of records in "Product Frontend Grid" should be 3
    And I should see "PSKU2" product
    And I should see "PSKU3" product
    And I should see "PSKU4" product
    When I reload the page
    Then number of records in "Product Frontend Grid" should be 3
    And I should see "PSKU2" product
    And I should see "PSKU3" product
    And I should see "PSKU4" product

  Scenario: Apply another filter
    Given I filter Text as does not contain "Product3"
    And I should see "Filter By Sub-Categories" filter with exact options in frontend product grid:
      | Architectural Floodlighting (1) |
      | Headlamps (1)                   |
    And number of records in "Product Frontend Grid" should be 2
    And I should see "PSKU2" product
    And I should see "PSKU4" product

    When I filter Text as does not contain "Product2"
    And I should see "Filter By Sub-Categories" filter with exact options in frontend product grid:
      | Architectural Floodlighting (0) |
      | Headlamps (2)                   |
    And number of records in "Product Frontend Grid" should be 2
    And I should see "PSKU3" product
    And I should see "PSKU4" product

    When I filter Text as contains "Product1"
    And I should see "Filter By Sub-Categories" filter with exact options in frontend product grid:
      | Architectural Floodlighting (0) |
      | Headlamps (0)                   |
    And number of records in "Product Frontend Grid" should be 0
    And should see filter hints in frontend grid:
      | Any Text: contains "Product1"                          |
      | Sub-Categories: Architectural Floodlighting, Headlamps |
    When I reload the page
    And I should see "Filter By Sub-Categories" filter with exact options in frontend product grid:
      | Architectural Floodlighting (0) |
      | Headlamps (0)                   |
    And number of records in "Product Frontend Grid" should be 0
    And should see filter hints in frontend grid:
      | Any Text: contains "Product1"                          |
      | Sub-Categories: Architectural Floodlighting, Headlamps |

    When I filter Text as does not contain "Product2"
    And I should see "Filter By Sub-Categories" filter with exact options in frontend product grid:
      | Architectural Floodlighting (0) |
      | Headlamps (2)                   |
    And number of records in "Product Frontend Grid" should be 2
    And I should see "PSKU3" product
    And I should see "PSKU4" product

  Scenario: Hide filter
    Given I hide filter "Sub-Categories" in "ProductFrontendGrid" frontend grid
    When I filter Text as contains "Product1"
    Then I should not see an "Subcategories Filter" element
    And number of records in "Product Frontend Grid" should be 1
    And I should see "PSKU1" product

    When I reload the page
    Then I should not see an "Subcategories Filter" element
    And number of records in "Product Frontend Grid" should be 1
    And I should see "PSKU1" product

    When I filter Text as contains "Product2"
    Then I should not see an "Subcategories Filter" element
    When I show filter "Sub-Categories" in "ProductFrontendGrid" frontend grid
    Then I should see an "Subcategories Filter" element
    And number of records in "Product Frontend Grid" should be 1
    And I should see "PSKU2" product

  Scenario: Change type to "Do not include"
    Given I proceed as the Admin
    And I click "Lighting Products"
    And I click on "First Content Variant Expand Button"
    And I fill "Content Node Form" with:
      | Sub-Categories | Do not include |
    And I save form

    When I proceed as the Buyer
    And I filter Text as contains "Product"
    Then I should not see an "Subcategories Filter" element
    And number of records in "Product Frontend Grid" should be 1
    And I should see "PSKU1" product
    When I reload the page
    Then I should not see an "Subcategories Filter" element
    And number of records in "Product Frontend Grid" should be 1
    And I should see "PSKU1" product

  Scenario: Change type to "Include, show as filter"
    Given I proceed as the Admin
    And I click on "First Content Variant Expand Button"
    And I fill "Content Node Form" with:
      | Sub-Categories | Include, show as filter |
    And I save form

    When I proceed as the Buyer
    And I filter Text as contains "Pro"
    Then I should see an "Subcategories Filter" element
    And number of records in "Product Frontend Grid" should be 4
    And I should see "PSKU1" product
    And I should see "PSKU2" product
    And I should see "PSKU3" product
    And I should see "PSKU4" product
    When I reload the page
    Then I should see an "Subcategories Filter" element
    And number of records in "Product Frontend Grid" should be 4
    And I should see "PSKU1" product
    And I should see "PSKU2" product
    And I should see "PSKU3" product
    And I should see "PSKU4" product
