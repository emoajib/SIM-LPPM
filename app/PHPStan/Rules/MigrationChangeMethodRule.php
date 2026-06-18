<?php

namespace App\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;

class MigrationChangeMethodRule implements Rule
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

        if ($node instanceof Node\Expr\MethodCall && $node->name instanceof Node\Identifier) {
            $methodName = $node->name->toString();

            if ($methodName === 'change') {
                $errors[] = RuleError::create(
                    'Do not use ->change() on enum columns. Use drop+add column pattern instead.'
                );
            }
        }

        return $errors;
    }
}
