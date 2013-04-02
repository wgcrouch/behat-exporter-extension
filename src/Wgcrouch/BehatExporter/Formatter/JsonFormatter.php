<?php

namespace Wgcrouch\BehatExporter\Formatter;

use Behat\Behat\Definition\DefinitionInterface,
    Behat\Behat\Definition\DefinitionSnippet,
    Behat\Behat\DataCollector\LoggerDataCollector,
    Behat\Behat\Event\SuiteEvent,
    Behat\Behat\Event\FeatureEvent,
    Behat\Behat\Event\ScenarioEvent,
    Behat\Behat\Event\BackgroundEvent,
    Behat\Behat\Event\OutlineEvent,
    Behat\Behat\Event\OutlineExampleEvent,
    Behat\Behat\Event\StepEvent,
    Behat\Behat\Event\EventInterface,
    Behat\Behat\Exception\UndefinedException;

use Behat\Behat\Formatter\ProgressFormatter as BaseFormatter;

/**
 * Formatter to output Features and step definitions as JSON
 * 
 */
class JsonFormatter extends BaseFormatter
{
        
    /**
     * Are we in background.
     *
     * @var Boolean
     */
    protected $inBackground           = false;

    /**
     * Array to store the features we have been through
     * @var array
     */
    protected $features = array();

    /**
     * The current Feature being processed
     * @var array
     */
    protected $currentFeature = array();

    /**
     * The current Scenario being processed
     * @var array
     */
    protected $currentScenario = array();

    /**
     * Array of step definitions we have seen
     * @var array
     */
    protected $definitions = array();

    /**
     * {@inheritdoc}
     */
    protected function getDefaultParameters()
    {
        return array();
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        $events = array(
            'afterSuite',
            'beforeFeature',
            'afterFeature',
            'beforeScenario',
            'afterScenario',
            'beforeBackground',
            'afterBackground',
            'beforeOutline',
            'afterOutline',
            'afterStep'
        );

        return array_combine($events, $events);
    }

    /**
     * Listens to "suite.after" event. Print out the arrays we have built up as json
     *
     * @param SuiteEvent $event
     *
     * @uses printSuiteFooter()
     */
    public function afterSuite(SuiteEvent $event)
    {
        print "DEFINITIONS\n";
        print json_encode($this->definitions);
        print "\nFEATURES\n";
        print json_encode($this->features);
    }

    /**
     * Listens to "feature.before" event.
     *
     * @param FeatureEvent $event
     *
     * @uses printFeatureHeader()
     */
    public function beforeFeature(FeatureEvent $event)
    {
        $feature = $event->getFeature();
        $tags = $feature->getOwnTags();
        $this->currentFeature = array(
            'name' => $feature->getTitle(),
            'tags' => $tags,
            'description' => $feature->getDescription(),
            'scenarios' => array()
        );
    }

    /**
     * Listens to "feature.after" event.
     *
     * @param FeatureEvent $event
     *
     * @uses printFeatureFooter()
     */
    public function afterFeature(FeatureEvent $event)
    {
        $this->features[] = $this->currentFeature;
    }

    /**
     * Listens to "background.before" event.
     *
     * @param BackgroundEvent $event
     *
     * @uses printBackgroundHeader()
     */
    public function beforeBackground(BackgroundEvent $event)
    {
        $this->inBackground = true;
    }

    /**
     * Listens to "background.after" event.
     *
     * @param BackgroundEvent $event
     *
     * @uses printBackgroundFooter()
     */
    public function afterBackground(BackgroundEvent $event)
    {
        $this->inBackground = false;
    }

    /**
     * Listens to "outline.before" event.
     *
     * @param OutlineEvent $event
     *
     * @uses printOutlineHeader()
     */
    public function beforeOutline(OutlineEvent $event)
    {
        $outline = $event->getOutline();
        $this->currentScenario = $this->createScenario($outline, 'outline');
    }

    /**
     * Listens to "outline.after" event.
     *
     * @param OutlineEvent $event
     *
     * @uses printOutlineFooter()
     */
    public function afterOutline(OutlineEvent $event)
    {
        $this->currentFeature['scenarios'][] = $this->currentScenario;
    }

    protected function createScenario($scenario, $type) {
        return array(
            'type' => $type,
            'title' => $scenario->getTitle(),
            'tags' => $scenario->getTags(),
            'steps' => array(),
            'path' => $this->relativizePathsInString($scenario->getFile()).':'.$scenario->getLine(),
            'background' => array()
        );  
    }

    /**
     * Listens to "scenario.before" event.
     *
     * @param ScenarioEvent $event
     *
     * @uses printScenarioHeader()
     */
    public function beforeScenario(ScenarioEvent $event)
    {
        $scenario = $event->getScenario();
        $this->currentScenario = $this->createScenario($scenario, 'scenario');
    }

    /**
     * Listens to "scenario.after" event.
     *
     * @param ScenarioEvent $event
     *
     * @uses printScenarioFooter()
     */
    public function afterScenario(ScenarioEvent $event)
    {
        $this->currentFeature['scenarios'][] = $this->currentScenario;        
    }

    /**
     * Listens to "step.after" event.
     *
     * @param StepEvent $event
     *
     * @uses printStep()
     */
    public function afterStep(StepEvent $event)
    {

        $step = $event->getStep();
        
        $newStep = array(
            'type' =>  $step->getType(),
            'text' => $step->getText(),
            'arguments' => array(),
            ''
        );

        foreach ($step->getArguments() as $argument) {
            $newStep['arguments'][] = (string) $argument;
        };

        $definition = $event->getDefinition();
        if ($definition) {
            $this->definitions[$definition->getRegex()] = array(
                'regex' => $definition->getRegex(),
                'description' => $definition->getDescription(),
                'path' => $this->relativizePathsInString($definition->getPath())
            );
            $newStep['definition'] = $definition->getRegex();
        } else {
            $newStep['definition'] = false;
        }

        if ($this->inBackground) {
            $this->currentFeature['background'] = $newStep;
        } else {
            $this->currentScenario['steps'][]  = $newStep;
        }
    }

}
