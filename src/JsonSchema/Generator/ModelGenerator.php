<?php

namespace Jane\JsonSchema\Generator;

use Jane\JsonSchema\Generator\Context\Context;
use Jane\JsonSchema\Generator\Model\ClassGenerator;
use Jane\JsonSchema\Generator\Model\GetterSetterGenerator;
use Jane\JsonSchema\Generator\Model\PropertyGenerator;
use Jane\JsonSchema\Guesser\Guess\ClassGuess;
use Jane\JsonSchema\Guesser\Guess\Property;
use Jane\JsonSchema\Schema;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Parser;

class ModelGenerator implements GeneratorInterface
{
    use ClassGenerator;
    use GetterSetterGenerator;
    use PropertyGenerator;

    const FILE_TYPE_MODEL = 'model';

    /**
     * @var Naming Naming Service
     */
    protected $naming;

    /**
     * @var Parser PHP Parser
     */
    protected $parser;

    /**
     * @param Naming $naming Naming Service
     * @param Parser $parser PHP Parser
     */
    public function __construct(Naming $naming, Parser $parser)
    {
        $this->naming = $naming;
        $this->parser = $parser;
    }

    /**
     * The naming service.
     *
     * @return Naming
     */
    protected function getNaming()
    {
        return $this->naming;
    }

    /**
     * {@inheritdoc}
     */
    protected function getParser()
    {
        return $this->parser;
    }

    /**
     * Generate a model given a schema.
     */
    public function generate(Schema $schema, string $className, Context $context)
    {
        foreach ($schema->getClasses() as $class) {
            $properties = [];
            $methods = [];

            /** @var Property $property */
            foreach ($class->getProperties() as $property) {
                $required = !$property->isNullable() && $context->isStrict();
                $properties[] = $this->createProperty($property, $schema->getNamespace() . '\\Model');
                $methods = array_merge($methods, $this->doCreateClassMethods($class, $property, $schema->getNamespace() . '\\Model', $required));
            }

            $model = $this->doCreateModel($class, $properties, $methods);

            $namespace = new Stmt\Namespace_(new Name($schema->getNamespace() . '\\Model'), [$model]);

            $schema->addFile(new File($schema->getDirectory() . '/Model/' . $class->getName() . '.php', $namespace, self::FILE_TYPE_MODEL));
        }
    }

    protected function doCreateClassMethods(ClassGuess $classGuess, Property $property, string $namespace, bool $required)
    {
        $methods = [];
        $methods[] = $this->createGetter($property, $namespace, $required);
        $methods[] = $this->createSetter($property, $namespace, $required);

        return $methods;
    }

    protected function doCreateModel(ClassGuess $class, $properties, $methods): Stmt\Class_
    {
        return $this->createModel(
            $class->getName(),
            $properties,
            $methods,
            \count($class->getExtensionsType()) > 0
        );
    }
}
