Feature: Product Overview Page

  Scenario: open category
    Given I go to "/en/home"
    When I follow "Women"
    Then I should be on "/en/women"
    And the ".breadcrumb" element should contain "Women"

  Scenario: open a product
    Given I go to "/en/search"
    When I follow "EUR"
    Then the url should match "html$"

  Scenario: sort by price asc
    Given I go to "/en/search"
    When I select "price-asc" from "sort"
    Then the parameter "sort" should be "price-asc"

  Scenario: sort by price desc
    Given I go to "/en/search"
    When I select "price-desc" from "sort"
    Then the parameter "sort" should be "price-desc"

  Scenario: sort by new
    Given I go to "/en/search?sort=price-asc"
    When I select "new" from "sort"
    Then the parameter "sort" should be "new"

  Scenario: switch page
    Given I go to "/en/search"
    When I follow "2"
    Then the parameter "page" should be "2"
    And the link "2" should have class "active"

  Scenario: change products per page
    Given I go to "/en/search"
    When I select "24" from "pageSize"
    Then the parameter "pageSize" should be "24"
    And I should see "24" in the ".item-list-pagination .item-count" element

  Scenario: change size
    Given I go to "/en/search"
    When I check "XXS"
    Then the parameter "size" should be "XXS"
    And the "XXS" checkbox should be checked
    When I uncheck "XXS"
    Then the parameter "size" should not be "XXS"
    And the "XXS" checkbox should not be checked

  Scenario: facet allows multiple values
    Given I go to "/en/search"
    When I check "XXS"
    And I check "XS"
    And the "XXS" checkbox should be checked
    And the "XS" checkbox should be checked
    When I uncheck "XXS"
    And the "XS" checkbox should be checked
    And the "XXS" checkbox should not be checked
