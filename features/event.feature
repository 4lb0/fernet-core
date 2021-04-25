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

  Scenario: Change content after all the components are proccessed
    Given the component defined in the class
    """
    class OnReadyContainer
    {
      public string $title = 'First';
      public function __toString(): string
      {
        $title = onReady(fn() => $this->title);
        return "<html><body><h1>$title</h1><OnReadyElement /></body></html>";
      }
    }
    """
    And the component defined in the class
    """
    class OnReadyElement
    {
      public function __construct(private OnReadyContainer $container)
      {
      }
      public function __toString(): string
      {
        $this->container->title = 'Second';
        return '<p>Ready</p>';
      }
    }
    """
    When the main component is "OnReadyContainer"
    And go to "/"
    Then I can see the text "Second" on "h1"
