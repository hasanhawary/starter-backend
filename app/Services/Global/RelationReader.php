<?php

namespace App\Services\Global;

use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionException;

class RelationReader
{
    public function __construct(public string $model)
    {
    }

    /**
     * @throws ReflectionException
     */
    public function getRelations(): array | string
    {
        $class = detectModelPath($this->model);

        if (!class_exists($class)) {
            return 'Model class does not exist.';
        }
        $model = new $class;

        $class = new ReflectionClass($model);

        $methods = $class->getMethods();

        $relations = [];

        foreach ($methods as $method) {

            if ($method->class !== get_class($model)) {
                continue;
            }

            if ($method->getNumberOfParameters() > 0) {
                continue;
            }

            $returnType = $method->getReturnType();

            if (!$returnType) {
                continue;
            }

            $typeName = $returnType->getName();

            if (is_subclass_of($typeName, Relation::class)) {

                $relations[]=$method->getName();
            }
        }

        return [$this->model=>$relations];
    }
}
