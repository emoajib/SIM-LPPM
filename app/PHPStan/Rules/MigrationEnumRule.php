<?php

namespace App\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

class MigrationEnumRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    public function processNode(
        Node $node,
        Scope $scope
    ): array {
        $errors = [];

        if (! $node instanceof Node\Expr\MethodCall) {
            return $errors;
        }

        if ($node->name instanceof Node\Identifier && $node->name->toString() === 'enum') {
            $errors[] = RuleErrorBuilder::message('Do not use $table->enum() in migrations. Use string + CHECK constraint pattern instead.')
                ->build();
        }

        return $errors;
    }
}
