<?php

namespace App\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;

class MigrationEnumRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\Call::class;
    }

    public function processNode(
        Node\Expr\Call $node,
        Scope $scope
    ): array {
        $errors = [];

        if ($node->name instanceof Node\Name) {
            $methodName = $node->name->toString();

            if ($methodName === 'enum') {
                $errors[] = RuleError::create(
                    "Do not use \$table->enum() in migrations. Use string + CHECK constraint pattern instead."
                );
            }
        }

        return $errors;
    }
}