Feature: Home page

  Scenario: open page
    When I go to "/"
    Then I should see "Sunrise"

  Scenario: view new products
    When I go to "/en/home"
    And I follow "Shop Collection"
    Then I should be on "/en/men-shoes-lace-up-shoes"
    Then I should see "Shoes"
