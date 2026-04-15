<?php

class Projet
{
    public static function getAvailableProjects(): array
    {
        throw new BadMethodCallException('This method is implemented in the controller.');
    }

    public static function getInvestmentsByUser(int $userId): array
    {
        throw new BadMethodCallException('This method is implemented in the controller.');
    }

    public static function getProjectRequestsByUser(int $userId): array
    {
        throw new BadMethodCallException('This method is implemented in the controller.');
    }

    public static function getProjectById(int $projectId): ?array
    {
        throw new BadMethodCallException('This method is implemented in the controller.');
    }

    public static function createProject(array $data): int
    {
        throw new BadMethodCallException('This method is implemented in the controller.');
    }
}
