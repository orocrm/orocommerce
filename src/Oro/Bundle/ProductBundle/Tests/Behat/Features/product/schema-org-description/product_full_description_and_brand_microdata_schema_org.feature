@ticket-BB-21658

Feature: Product full description and brand microdata schema org
  In order to have full description schema.org on website product lists and view pages
  As an Administrator
  I want to have ability to change product description that used in schema.org microdata description used on product pages
  As a Guest
  I want to have ability to view full product description schema.org on product list and view pages

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I set configuration property "oro_product.related_products_min_items" to "1"
    And I set configuration property "oro_product.upsell_products_min_items" to "1"
    And I disable configuration options:
      | oro_product.microdata_without_prices_disabled |
    And I set configuration property "oro_product.schema_org_description_field" to "oro_product_full_description"

    And I add Featured Products widget after content for "Homepage" page
    And I update settings for "featured-products" content widget:
      | minimum_items | 1 |

    And I proceed as the Admin
    And I login as administrator

  Scenario: Prepare test product with descriptions and brand
    Given I go to Products/ Brand
    And click "Create Brand"
    And I fill "Brand Form" with:
      | Name | Test Brand |
    When I save and close form
    Then I should see "Brand has been saved" flash message
    And I go to Products/ Products
    And click "Create Product"
    And I click "Continue"
    And I fill "ProductForm" with:
      | SKU         | TestSKU123                           |
      | Name        | Test Product                         |
      | Brand       | Test Brand                           |
      | Status      | Enable                               |
      | Is Featured | Yes                                  |
      | Description | <p>Test Product Full Description</p> |
    When I save and duplicate form
    Then I should see "Product has been saved and duplicated" flash message
    And I click "Edit"
    And I fill "ProductForm" with:
      | SKU    | TestSKU456 |
      | Status | Enable     |
    And I save and duplicate form
    And I click "Edit"
    And I fill "ProductForm" with:
      | SKU    | TestSKU789 |
      | Status | Enable     |
    When I click "Select related products"
    Then I select following records in "SelectRelatedProductsGrid" grid:
      | TestSKU123 |
      | TestSKU456 |
    And I click "Select products"
    And I click "Up-sell Products"
    And I click "Select up-sell products"
    And I select following records in "SelectUpsellProductsGrid" grid:
      | TestSKU123 |
      | TestSKU456 |
    And I click "Select products"
    And I save form

  Scenario: Check full schema.org product description and brand in featured products on the store frontend
    Given I proceed as the Buyer
    When I go to the homepage
    Then "TestSKU456" product in "Featured Products Block" should contains microdata:
      | Product Type Microdata Declaration  |
      | Product Brand Microdata Declaration |
      | SchemaOrg Description               |
      | SchemaOrg Brand Name                |
    And "TestSKU456" product in "Featured Products Block" should contains "SchemaOrg Description" with attributes:
      | content | Test Product Full Description |
    And "TestSKU456" product in "Featured Products Block" should contains "SchemaOrg Brand Name" with attributes:
      | content | Test Brand |

  Scenario: Check full schema.org product description and brand in search product list on the store frontend
    When I type "TestSKU789" in "search"
    And I click "Search Button"
    Then "TestSKU789" product in "Product Frontend Grid" should contains microdata:
      | Product Type Microdata Declaration  |
      | Product Brand Microdata Declaration |
      | SchemaOrg Description               |
      | SchemaOrg Brand Name                |
    And "TestSKU789" product in "Product Frontend Grid" should contains "SchemaOrg Description" with attributes:
      | content | Test Product Full Description |
    And "TestSKU789" product in "Product Frontend Grid" should contains "SchemaOrg Brand Name" with attributes:
      | content | Test Brand |

  Scenario: Check full schema.org product description and brand in product view page on the store frontend
    When I click "View Details" for "TestSKU789" product
    Then "Product Item View" should contains microdata:
      | Product Type Microdata Declaration  |
      | Product Brand Microdata Declaration |
      | SchemaOrg Description               |
      | SchemaOrg Brand Name                |
    And "Product Item View" should contains "SchemaOrg Description" with attributes:
      | content | Test Product Full Description |
    And "Product Item View" should contains microdata elements with text:
      | SchemaOrg Brand Name | Test Brand |

    And "TestSKU123" product in "Related Products Block" should contains microdata:
      | Product Type Microdata Declaration  |
      | Product Brand Microdata Declaration |
      | SchemaOrg Description               |
      | SchemaOrg Brand Name                |
    And "TestSKU123" product in "Related Products Block" should contains "SchemaOrg Description" with attributes:
      | content | Test Product Full Description |
    And "TestSKU123" product in "Related Products Block" should contains "SchemaOrg Brand Name" with attributes:
      | content | Test Brand |

    And "TestSKU456" product in "Upsell Products Block" should contains microdata:
      | Product Type Microdata Declaration  |
      | Product Brand Microdata Declaration |
      | SchemaOrg Description               |
      | SchemaOrg Brand Name                |
    And "TestSKU456" product in "Upsell Products Block" should contains "SchemaOrg Description" with attributes:
      | content | Test Product Full Description |
    And "TestSKU456" product in "Upsell Products Block" should contains "SchemaOrg Brand Name" with attributes:
      | content | Test Brand |

