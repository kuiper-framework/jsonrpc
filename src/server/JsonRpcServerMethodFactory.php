<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use Exception;
use kuiper\reflection\exception\ClassNotFoundException;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\rpc\exception\InvalidMethodException;
use kuiper\rpc\RpcMethod;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\server\Service;
use kuiper\serializer\NormalizerInterface;
use ReflectionException;

class JsonRpcServerMethodFactory implements RpcMethodFactoryInterface
{
    /**
     * @var array
     */
    private array $cachedTypes;

    /**
     * @param array<string, Service>             $services
     * @param NormalizerInterface                $normalizer
     * @param ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory
     */
    public function __construct(
        private readonly array $services,
        private readonly NormalizerInterface $normalizer,
        private readonly ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function create(object|string $service, string $method, array $args): RpcMethodInterface
    {
        $serviceName = $service;
        if (!isset($this->services[$serviceName])) {
            $serviceName = str_replace('\\', '.', $serviceName);
        }
        if (!isset($this->services[$serviceName])) {
            throw new InvalidMethodException("jsonrpc service $service not found");
        }
        $serviceObject = $this->services[$serviceName];
        if (!$serviceObject->hasMethod($method)) {
            throw new InvalidMethodException("jsonrpc method $service.$method not found");
        }
        try {
            $arguments = $this->resolveParams($serviceObject, $method, $args);
        } catch (Exception $e) {
            throw new InvalidMethodException("create method $service.$method parameters fail: ".$e->getMessage());
        }

        return new RpcMethod($serviceObject->getService(), $serviceObject->getServiceLocator(), $method, $arguments);
    }

    private function resolveParams(Service $service, string $methodName, array $params): array
    {
        $paramTypes = $this->getParameterTypes($service, $methodName);
        $ret = [];
        foreach ($paramTypes as $i => $type) {
            $ret[] = $this->normalizer->denormalize($params[$i], $type);
        }

        return $ret;
    }

    /**
     * @throws ReflectionException
     * @throws ClassNotFoundException
     */
    private function getParameterTypes(Service $service, string $methodName): array
    {
        $key = $service->getServiceName().'::'.$methodName;
        if (isset($this->cachedTypes[$key])) {
            return $this->cachedTypes[$key];
        }

        $reflectionMethod = $service->getMethod($methodName);
        $reflectionMethodDocBlock = $this->reflectionDocBlockFactory->createMethodDocBlock($reflectionMethod);

        return $this->cachedTypes[$key] = array_values($reflectionMethodDocBlock->getParameterTypes());
    }

    /**
     * @return array<string, Service>
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @return NormalizerInterface
     */
    public function getNormalizer(): NormalizerInterface
    {
        return $this->normalizer;
    }

    /**
     * @return ReflectionDocBlockFactoryInterface
     */
    public function getReflectionDocBlockFactory(): ReflectionDocBlockFactoryInterface
    {
        return $this->reflectionDocBlockFactory;
    }
}
