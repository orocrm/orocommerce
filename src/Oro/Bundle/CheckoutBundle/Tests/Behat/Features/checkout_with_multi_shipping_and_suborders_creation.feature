@ticket-BB-21182
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:CheckoutCustomerFixture.yml
@fixture-OroCheckoutBundle:ProductsAndCategoriesForMultiShippingFixture.yml
@fixture-OroCheckoutBundle:ShoppingListForMultiShippingFixture.yml
@fixture-OroCheckoutBundle:Order.yml

Feature: Checkout With Multi Shipping And Suborders Creation
  In order to create separate sub orders
  As a Customer User
  I want to see separate sub orders after checkout

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Enable shipping method selection per line item and grouping and suborders
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Sales/Multi Shipping Options" on configuration sidebar
    And uncheck "Use default" for "Enable shipping method selection per line item" field
    When I fill form with:
      | Enable shipping method selection per line item | true |
    Then I should see "Make sure to create Multi Shipping integration when enabling Shipping method selection per line item, otherwise it will break the checkout flow."
    When I click "Create Multi Shipping Integration"
    Then I should see "Multi shipping integration was created successfully" flash message
    And I should not see "Make sure to create Multi Shipping integration when enabling Shipping method selection per line item, otherwise it will break the checkout flow."
    And uncheck "Use default" for "Enable grouping of line items during checkout" field
    And I fill form with:
      | Enable grouping of line items during checkout | true |
    And uncheck "Use default" for "Group line items by" field
    And I fill form with:
      | Group line items by | Owner |
    And uncheck "Use default" for "Create Sub-Orders for each group" field
    And I fill form with:
      | Create Sub-Orders for each group | true |
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Checkout with shipping method selection per line item
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    And I should see following "Multi Shipping Shopping List" grid:
      | SKU  | Item                                | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light            | $2.00  | $10.00   |
      | SKU2 | iPhone 13                           | $2.00  | $20.00   |
      | SKU3 | iPhone X                            | $2.00  | $20.00   |
      | SKU4 | Round Meeting Table, 30 in. x 30in. |        |          |
    And I should see notification "This product will be available later" for "SKU1" line item "Checkout Line Item"
    And I should see notification "This product will be available later" for "SKU3" line item "Checkout Line Item"
    When I click "Create Order"
    Then Page title equals to "Billing Information - Checkout"
    # Grouped line items grids should be visible starting from first step.
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see an "Second Business Unit" element
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal |
      | SKU2 | iPhone 13 | 10  | $2.00  | $20.00   |
      | SKU3 | iPhone X  | 10  | $2.00  | $20.00   |
    And records in "Second Checkout Shipping Grid" should be 2
    And I click "Continue"
    Then Page title equals to "Shipping Information - Checkout"
    And I click "Continue"
    Then Page title equals to "Shipping Method - Checkout"
    And I should see an "Common Business Unit Name" element
    And I should see following "First Checkout Shipping Grid" grid:
      | SKU  | Item                     | Qty | Price  | Subtotal | Shipping         |
      | SKU1 | 400-Watt Bulb Work Light | 5   | $2.00  | $10.00   | Flat Rate: $3.00 |
    And records in "First Checkout Shipping Grid" should be 1
    And I should see an "Second Business Unit" element
    And I should see following "Second Checkout Shipping Grid" grid:
      | SKU  | Item      | Qty | Price  | Subtotal | Shipping         |
      | SKU2 | iPhone 13 | 10  | $2.00  | $20.00   | Flat Rate: $3.00 |
      | SKU3 | iPhone X  | 10  | $2.00  | $20.00   | Flat Rate: $3.00 |
    And records in "Second Checkout Shipping Grid" should be 2
    And I should see Checkout Totals with data:
      | Subtotal | $50.00 |
      | Shipping | $9.00  |
    When I click "Continue"
    Then Page title equals to "Payment - Checkout"
    And I click "Continue"
    Then Page title equals to "Order Review - Checkout"
    And I fill "Checkout Order Review Form" with:
      | PO Number | PO1 |
    When I click "Submit Order"
    Then I should see "Thank You For Your Purchase!"
    When I open Order History page on the store frontend
    Then I should see following "Past Orders Grid" grid:
      | Order Number |
      | 2-2          |
      | 2-1          |
      | 2            |
      | SimpleOrder  |
    And records in "Past Orders Grid" should be 4
    Then I should see available "Order Type" filter in "Past Orders Grid" frontend grid
    When I proceed as the Admin
    And I go to Sales/Orders
    Then I should see following grid:
      | Order Number | Owner          |
      | SimpleOrder  | John Doe       |
      | 2            | John Doe       |
      | 2-1          | SubOrder Owner |
      | 2-2          | SubOrder Owner |
    And number of records should be 4
    # Click on order number 2
    When I click View "$59.00" in grid
    Then I should see "Sub-Orders"
    When I scroll to "SubOrders Grid"
    Then I should see following "SubOrders Grid" grid:
      | Order Number | Total  |
      | 2-1          | $13.00 |
      | 2-2          | $46.00 |
    And records in "SubOrders Grid" should be 2
    When I click view "$13.00" in grid
    Then I should see "Order #2-1"
    And I should see "Parent Order #2"
    And I should see "PO Number PO1"
    And I should see following "BackendOrderLineItemsGrid" grid:
      | Sku   |
      | SKU1  |
    And number of records should be 1
    When I click "#2"
    Then I should see "Sub-Orders"
    When I scroll to "SubOrders Grid"
    When I click view "$46.00" in grid
    Then I should see "Order #2-2"
    And I should see "Parent Order #2"
    And I should see "PO Number PO1"
    And I should see following "BackendOrderLineItemsGrid" grid:
      | Sku  |
      | SKU2 |
      | SKU3 |
    And number of records should be 2

  Scenario: Hide suborders in order history
    Given I go to System/Configuration
    And I follow "Commerce/Sales/Multi Shipping Options" on configuration sidebar
    And uncheck "Use default" for "Show Sub-Orders in order history" field
    And I fill form with:
      | Show Sub-Orders in order history | false |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check main orders in order history
    Given I proceed as the Buyer
    And I reload the page
    Then I should see following "Past Orders Grid" grid:
      | Order Number |
      | 2            |
      | SimpleOrder  |
    And records in "Past Orders Grid" should be 2
    Then I should see no available "Order Type" filter in "Past Orders Grid" frontend grid

  Scenario: Show suborders and hide main orders in order history
    Given I proceed as the Admin
    And I fill form with:
      | Show Sub-Orders in order history | true |
    And uncheck "Use default" for "Show Main Orders in order history" field
    And I fill form with:
      | Show Main Orders in order history | false |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check suborders in order history
    Given I proceed as the Buyer
    And I reload the page
    Then I should see following "Past Orders Grid" grid:
      | Order Number |
      | 2-2          |
      | 2-1          |
      | SimpleOrder  |
    And records in "Past Orders Grid" should be 3
    Then I should see no available "Order Type" filter in "Past Orders Grid" frontend grid

  Scenario: Disable suborders while main orders are disabled in order history
    Given I proceed as the Admin
    And I fill form with:
      | Show Sub-Orders in order history | false |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check main orders in order history
    Given I proceed as the Buyer
    And I reload the page
    Then I should see following "Past Orders Grid" grid:
      | Order Number |
      | 2            |
      | SimpleOrder  |
    And records in "Past Orders Grid" should be 2
    Then I should see no available "Order Type" filter in "Past Orders Grid" frontend grid
