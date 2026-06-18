<?php

namespace App\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;

class MigrationRawStatementRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\StaticCall::class;
    }

    public function processNode(
        Node\Expr\StaticCall $node,
        Scope $scope
    ): array {
        $errors = [];

        if ($node->class instanceof Node\Name) {
            $className = $node->class->toString();

            if ($className === 'DB' && $node->name instanceof Node\Identifier) {
                $methodName = $node->name->toString();

                if ($methodName === 'statement') {
                    $errors[] = RuleError::create(
                        "Do not use raw DB::statement() for schema changes in migrations. Use Blueprint methods instead."
                    );
                }
            }
        }

        return $errors;
    }
}