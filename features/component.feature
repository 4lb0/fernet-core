Feature: Components
  In order to break down the app
  As a developer
  I need to built encapsulated components

  Scenario: Creating a simple static component
    Given the component defined in the class
    """
    class SimpleStaticTag
    {
      public function __toString(): string
      {
        return '<p class="something">simple static tag</p>';
      }
    }
    """
    When the framework is run with component "SimpleStaticTag"
    Then the output is '<p class="something">simple static tag</p>'

  Scenario: Creating a component with a component
    Given the component defined in the class
    """
    class FooBarText
    {
      public function __toString(): string
      {
        return '<p>FooBar</p>';
      }
    }
    """
    And the component defined in the class
    """
    class ComponentContainsAnother
    {
      public function __toString(): string
      {
        return '<div><FooBarText /></div>';
      }
    }
    """
    When the framework is run with component "ComponentContainsAnother"
    Then the output is '<div><p>FooBar</p></div>'

  Scenario: You can define a component with a param.
    Given the component defined in the class
    """
    class Hello
    {
      public string $name = '';
      public function __toString(): string
      {
        return "<p>Hello {$this->name}</p>";
      }
    }
    """
    And the component defined in the class
    """
    class HelloApp
    {
      public function __toString(): string
      {
        return '<Hello name="Sara" />';
      }
    }
    """
    When the framework is run with component "HelloApp"
    Then the output is '<p>Hello Sara</p>'


  Scenario: You can define a component with content children.
    Given the component defined in the class
    """
    class HelloChildren
    {
      public function __toString(): string
      {
        return "<p>Hello {$this->childContent}</p>";
      }
    }
    """
    And the component defined in the class
    """
    class HelloChildrenApp
    {
      public function __toString(): string
      {
        return '<HelloChildren>Carmencita</HelloChildren>';
      }
    }
    """
    When the framework is run with component "HelloChildrenApp"
    Then the output is '<p>Hello Carmencita</p>'


  Scenario: To create a component with a non string as a parameter we need
            to use the Param::component static method helper.
    Given the entity defined in the class
    """
    class ObjectParamUser
    {
      public string $name = '';
    }
    """
    And the component defined in the class
    """
    class HelloObject
    {
      public ObjectParamUser $user;

      public function __construct()
      {
        $this->user = new ObjectParamUser();
      }

      public function __toString(): string
      {
        return "<p>Hello {$this->user->name}</p>";
      }
    }
    """
    And the component defined in the class
    """
    use Fernet\Params;
    class HelloObjectApp
    {
      public function __toString(): string
      {
        $user = new ObjectParamUser();
        $user->name = 'John';
        $params = Params::component(['user' => $user]);
        return "<HelloObject {$params} />";
      }
    }
    """
    When the framework is run with component "HelloObjectApp"
    Then the output is '<p>Hello John</p>'

  Scenario: Be careful with circular reference in the components.
    Given the component defined in the class
    """
    class CircularReferenceA
    {
      public function __toString(): string
      {
        return "<CircularReferenceB />";
      }
    }
    """
    And the component defined in the class
    """
    class CircularReferenceB
    {
      public function __toString(): string
      {
        return "<CircularReferenceA />";
      }
    }
    """
    When the framework is run with component "CircularReferenceA"
    Then the output is an error 500

  Scenario: When you run a not found component you get a 404 error.
    When the framework is run with component "ThisComponentNotExists"
    Then the output is an error 404
