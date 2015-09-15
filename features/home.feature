Feature: Home page

  Scenario: open page
    When I go to "/"
    Then I should see "Sunrise"

  Scenario: view new products
    When I go to "/"
    And I follow "Shop Collection"
    Then I should see "Shoes"
