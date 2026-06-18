<?php

namespace App\PHPStan\Rules;

use PhpParser\Node;
use PHPParser\Node\Expr\BinaryOp\Identical;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;

class MigrationDriverBranchRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\BinaryOp\Identical::class;
    }

    public function processNode(
        Node\Expr\BinaryOp\Identical $node,
        Scope $scope
    ): array {
        $errors = [];

        if ($node->left instanceof Node\Expr\PropertyFetch) {
            $propertyFetch = $node->left;

            if ($propertyFetch->name instanceof Node\Identifier) {
                $propertyName = $propertyFetch->name->toString();

                if ($propertyName === 'driver') {
                    $errors[] = RuleError::create(
                        "Do not use driver-specific branches (if (\$driver === 'pgsql')). Use portable migration patterns."
                    );
                }
            }
        }

        return $errors;
    }
}