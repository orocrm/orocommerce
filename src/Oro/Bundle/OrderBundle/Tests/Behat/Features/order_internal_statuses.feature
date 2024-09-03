@ticket-BB-9594
@fixture-OroOrderBundle:order.yml
Feature: Order Internal Statuses
  In order to change order statuses
  As an Administrator
  I want to have actions at view order page to change internal statuses

  Scenario: Verify internal statuses flow Open => Shipped => Closed => Archived
    Given I login as administrator
    And I go to Sales/Orders
    When I click view "SimpleOrder" in grid
    Then I should see that order internal status is "Open"
    And I should see following buttons:
      | Mark as Shipped |
      | Cancel          |
      | Close           |
    And I should not see following buttons:
      | Archive |

    When I click on page action "Mark As Shipped"
    Then I should see "Order #SimpleOrder has been marked as shipped." flash message
    And I should see that order internal status is "Shipped"
    And I should see following buttons:
      | Close |
    And I should not see following buttons:
      | Mark as Shipped |
      | Cancel          |
      | Archive         |

    When I click "More actions"
    And I click "Close"
    And I click "Yes" in confirmation dialogue
    Then I should see "Order #SimpleOrder has been closed." flash message
    And I should see that order internal status is "Closed"
    And I should see following buttons:
      | Archive |
    And I should not see following buttons:
      | Cancel          |
      | Close           |
      | Mark as Shipped |

    When I click on page action "Archive"
    Then I should see "Order #SimpleOrder has been archived." flash message
    And I should see that order internal status is "Archived"
    And I should not see following buttons:
      | Cancel          |
      | Archive         |
      | Close           |
      | Mark as Shipped |

  Scenario: Verify internal statuses at BackOffice Order grid
    Given I go to Sales/Orders
    And there is one record in grid
    And I should see following grid:
      | Order Number | Internal Status |
      | SecondOrder  | Open            |
    When click grid view list
    And I click "All Orders"
    Then number of records should be 2
    When I sort grid by "Internal Status"
    Then I should see following grid:
      | Order Number | Internal Status |
      | SimpleOrder  | Archived        |
      | SecondOrder  | Open            |
    When I sort grid by "Internal Status" again
    Then I should see following grid:
      | Order Number | Internal Status |
      | SecondOrder  | Open            |
      | SimpleOrder  | Archived        |

  Scenario: Verify internal statuses flow Open => Cancelled => Closed => Archived
    Given I go to Sales/Orders
    When I click view "SecondOrder" in grid
    Then I should see that order internal status is "Open"
    And I should see following buttons:
      | Mark as Shipped |
      | Cancel          |
      | Close           |
    And I should not see following buttons:
      | Archive |

    When I click "More actions"
    And I click "Cancel"
    And I click "Yes" in confirmation dialogue
    Then I should see "Order #SecondOrder has been cancelled." flash message
    And I should see that order internal status is "Cancelled"
    And I should see following buttons:
      | Close |
    And I should not see following buttons:
      | Cancel          |
      | Archive         |
      | Mark as Shipped |

    When I click "More actions"
    And I click "Close"
    And I click "Yes" in confirmation dialogue
    Then I should see "Order #SecondOrder has been closed." flash message
    And I should see that order internal status is "Closed"
    And I should see following buttons:
      | Archive |
    And I should not see following buttons:
      | Cancel          |
      | Close           |
      | Mark as Shipped |

    When I click on page action "Archive"
    Then I should see "Order #SecondOrder has been archived." flash message
    And I should see that order internal status is "Archived"
    And I should not see following buttons:
      | Cancel          |
      | Archive         |
      | Close           |
      | Mark as Shipped |
