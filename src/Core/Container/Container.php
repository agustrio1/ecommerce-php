<?php

declare(strict_types=1);

namespace App\Core\Container;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Container
 *
 * Dependency Injection Container sederhana. Bisa:
 *   - bind(interface, implementation): daftarkan binding interface ke class konkret
 *   - singleton(interface, implementation): seperti bind, tapi instance di-cache (dibuat sekali saja)
 *   - make(class): resolve instance, otomatis inject dependency constructor lewat Reflection
 *
 * Pemakaian:
 *   $container->bind(UserRepositoryInterface::class, MysqlUserRepository::class);
 *   $service = $container->make(AuthService::class); // otomatis suntik UserRepositoryInterface
 */
class Container
{
    private static ?Container $instance = null;

    /** @var array<string, string|\Closure> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $singletons = [];

    /** @var array<string, object> instance singleton yang sudah pernah dibuat */
    private array $resolvedSingletons = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function bind(string $abstract, string|\Closure $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, string|\Closure $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
        $this->singletons[$abstract] = true;
    }

    public function make(string $abstract): object
    {
        if (isset($this->resolvedSingletons[$abstract])) {
            return $this->resolvedSingletons[$abstract];
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;

        $instance = $concrete instanceof \Closure
            ? $concrete($this)
            : $this->build($concrete);

        if (isset($this->singletons[$abstract])) {
            $this->resolvedSingletons[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Buat instance class lewat Reflection, otomatis resolve dependency
     * di constructor (jika berupa class/interface yang juga bisa di-resolve).
     */
    private function build(string $class): object
    {
        if (! class_exists($class)) {
            throw new RuntimeException("Class [{$class}] tidak ditemukan, tidak bisa di-resolve Container.");
        }

        $reflector = new ReflectionClass($class);

        if (! $reflector->isInstantiable()) {
            throw new RuntimeException("Class [{$class}] tidak bisa di-instantiate (abstract/interface).");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new RuntimeException(
                    "Tidak bisa resolve parameter [{$parameter->getName()}] di constructor [{$class}]."
                );
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}