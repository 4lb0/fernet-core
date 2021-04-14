Feature: Event
  In order to write less code
  As a developer
  I want to link directly one event to a component method

  Scenario: Creating a simple static component
    Given the component defined in the class
    """
    class SimpleEvent
    {
      private bool $routed = false;

      public function handleClick()
      {
        $this->routed = true;
      }

      public function __toString(): string
      {
        $text = $this->routed ? 'Yes' : 'No';
        return "<html><body><p>$text</p><a @href='handleClick'>Go</a></body></html>";
      }
    }
    """
    When the main component is "SimpleEvent"
    And go to "/"
    And the link "Go" is clicked
    Then I can see the text "Yes" on "p"
