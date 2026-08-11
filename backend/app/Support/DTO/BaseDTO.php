<?php

namespace App\Support\DTO;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use ReflectionClass;

/**
 * Base Data Transfer Object.
 *
 * Domain DTOs (e.g. ProgramDTO) extend this as a readonly, constructor-promoted
 * value object. Controllers build a DTO from the validated FormRequest and pass
 * it to the Service — Services never receive a raw Request or array
 * (ADR-0001 §2.2).
 *
 * @implements Arrayable<string, mixed>
 */
abstract class BaseDTO implements Arrayable
{
    /**
     * Build the DTO from a validated FormRequest.
     */
    public static function fromRequest(Request $request): static
    {
        /** @var array<string, mixed> $validated */
        $validated = method_exists($request, 'validated') ? $request->validated() : $request->all();

        return static::fromArray($validated);
    }

    /**
     * Build the DTO from a plain array, matching keys to constructor parameters.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): static
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        $arguments = [];
        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $attributes)) {
                $arguments[$name] = $attributes[$name];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[$name] = $parameter->getDefaultValue();
            } elseif ($parameter->allowsNull()) {
                $arguments[$name] = null;
            }
        }

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
