<?php

namespace Larrock\YandexKassa\Events;

use Larrock\YandexKassa\Requests\YandexKassaRequest;

class BeforeResponse extends YandexKassaEvent
{
    /**
     * @var \Larrock\YandexKassa\Requests\YandexKassaRequest
     */
    public $request;

    /**
     * @var array
     */
    public $responseParameters;

    /**
     * BeforeResponse constructor.
     * @param \Larrock\YandexKassa\Requests\YandexKassaRequest $request
     * @param array $responseParameters
     */
    public function __construct(YandexKassaRequest $request, $responseParameters)
    {
        $this->request = $request;
        $this->responseParameters = $responseParameters;
    }
}