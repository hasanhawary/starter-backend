<?php

namespace AiChat\Scanner;

class ProjectScanner
{
    public function __construct(
        private readonly ModelScanner $modelScanner,
        private readonly RelationScanner $relationScanner,
        private readonly RouteScanner $routeScanner,
        private readonly ControllerScanner $controllerScanner,
        private readonly ServiceScanner $serviceScanner,
        private readonly PolicyScanner $policyScanner,
        private readonly MigrationScanner $migrationScanner,
        private readonly ProjectMapBuilder $projectMapBuilder,
    ) {}

    public function scan(): array
    {
        $scanResults = [
            'models' => $this->modelScanner->scan(),
            'routes' => $this->routeScanner->scan(),
            'controllers' => $this->controllerScanner->scan(),
            'services' => $this->serviceScanner->scan(),
            'policies' => $this->policyScanner->scan(),
            'migrations' => $this->migrationScanner->scan(),
        ];

        $projectMap = $this->projectMapBuilder->build($scanResults);

        $this->projectMapBuilder->store($projectMap);

        return $projectMap;
    }
}
