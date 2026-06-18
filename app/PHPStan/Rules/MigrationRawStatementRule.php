<?php

namespace App\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

class MigrationRawStatementRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\StaticCall::class;
    }

    public function processNode(
        Node $node,
        Scope $scope
    ): array {
        $errors = [];

        if (! $node instanceof Node\Expr\StaticCall) {
            return $errors;
        }

        if ($node->class instanceof Node\Name) {
            $className = $node->class->toString();

            if ($className === 'DB' && $node->name instanceof Node\Identifier) {
                $methodName = $node->name->toString();

                if ($methodName === 'statement') {
                    $errors[] = RuleErrorBuilder::message('Do not use raw DB::statement() for schema changes in migrations. Use Blueprint methods instead.')
                        ->build();
                }
            }
        }

        return $errors;
    }
}
