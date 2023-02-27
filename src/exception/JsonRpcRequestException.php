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

namespace kuiper\jsonrpc\exception;

use InvalidArgumentException;

class JsonRpcRequestException extends InvalidArgumentException
{
    public function __construct(private readonly ?int $requestId, string $message, int $code)
    {
        parent::__construct($message, $code);
    }

    public function getRequestId(): ?int
    {
        return $this->requestId;
    }
}
