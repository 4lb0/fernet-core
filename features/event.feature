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
        $text = $this->clicked ? 'On' : 'Off';
        return "<html><body><p>$text</p><a onClick='handleClick'>Toggle</a></body></html>";
      }
    }
    """
    When the framework is run with component "SimpleEvent"
    And the link "Toggle" is clicked
    Then the I can see the text "On"
