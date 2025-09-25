<?php

namespace Moggie\Container\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

class EntryNotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
