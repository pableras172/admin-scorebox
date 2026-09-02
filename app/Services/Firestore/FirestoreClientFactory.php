<?php

declare(strict_types=1);

namespace App\Services\Firestore;

use Illuminate\Container\Container;

class FirestoreClientFactory
{
    public function __construct(protected ?Container $container = null)
    {
        $this->container = $container ?? app();
    }

    public function make(): object
    {
        $firestore = $this->container->make('firebase.firestore');

        if (! method_exists($firestore, 'database')) {
            throw new \RuntimeException('The Firebase Firestore binding is not available.');
        }

        return $firestore->database();
    }
}
