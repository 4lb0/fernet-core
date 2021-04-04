Feature: Event
  In order to write less code
  As a developer
  I want to link directly one event to a component method

  Scenario: Creating a simple static component
    Given the component defined in the class
    """
    class SimpleEvent
    {
      private bool $clicked = false;

      public function handleClick()
      {
        $this->clicked = true;
      }

      public function __toString(): string
      {
        $text = $this->clicked ? 'Yes' : 'No';
        return "<html><body><p>$text</p><a @onClick='handleClick'>Toggle</a></body></html>";
      }
    }
    """
    When the main component is "SimpleEvent"
    And go to "/"
    And the link "Toggle" is clicked
    Then I can see the text "Yes" on "p"
