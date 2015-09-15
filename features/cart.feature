Feature: Cart
  Background: open a product
    Given I start a new session
    And I go to "/en/search"
    When I follow "EUR"
    Then the url should match "html$"
    When I add the product to cart
    Then the url should match "html$"
    When I follow the ".list-item-bag .link-your-bag" link
    Then I should be on "/en/cart"

  Scenario: Add item to cart
    Then the ".list-item-bag .cart-item-number" element should contain "1"

  Scenario: change quantity
    And I should see an ".input-number-increment" element
    When I press the ".input-number-increment" element
    Then the "quantity" field should contain "2"
    When I press "line-item-edit-button"
    Then the "quantity" field should contain "2"

  Scenario: remove line item
    When I press "line-item-delete-button"
    Then the ".list-item-bag .cart-item-number" element should contain "0"

  Scenario: continue shopping
    When I press "cart-continueshopping-btn-xs"
    Then I should be on "/en/home"
