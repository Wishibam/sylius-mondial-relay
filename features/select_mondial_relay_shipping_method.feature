@mondial_relay
Feature: I can select Mondial Relay shipping method in the order tunnel
  Background:
    Given the store operates on a single channel in "France"
    And the store has a product "Reblochon" priced at "$19.99"
    And the store ships with mondial relay
    And the store allows paying with "Cash on Delivery"
    And I am a logged in customer

  Scenario: As a shop user I select mondial relay shipping method
    When a customer named "Krzysztof" visits static welcome page
    Then they should be statically greeted with "Hello, Krzysztof!"
