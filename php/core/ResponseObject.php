<?php

namespace php\core;

use Closure as ClosureAlias;
use php\core\utils\lang\StringUtils;

class ResponseObject
{
    /** @var int */
    private int $statusCode;

    /** @var string[] */
    private array $headers = [];

    /** @var ClosureAlias|null */
    private ?ClosureAlias $action = null;

    public static function ResultsObjectToJson($object, int $codeHttp = 200): ResponseObject
    {

        $rep = new ResponseObject();
        $rep->statusCode = $codeHttp;
        $rep->headers[] = 'Content-Type: application/json; charset=utf-8';
        $rep->action = function () use ($object) {
            echo json_encode(StringUtils::utf8ize($object), JSON_UNESCAPED_UNICODE);
        };

        return $rep;
    }


    public static function ResultCodeHttp(int $codeHttp = 200): ResponseObject
    {
        $rep = new ResponseObject();
        $rep->statusCode = $codeHttp;
        return $rep;
    }

    public static function RedirectTo(string $routeToRedirect, int $codeHttp = 200): ResponseObject
    {
        $rep = new ResponseObject();
        $rep->statusCode = $codeHttp;
        $rep->headers[] = "Location: " . $routeToRedirect;
        return $rep;
    }

    public static function DownloadFile(string $fileUrl, string $fileName, string $contentType = "application/octet-stream"): ResponseObject
    {
        $rep = new ResponseObject();
        $rep->statusCode = 200;
        $rep->headers[] = 'Content-Type: ' . $contentType;
        $rep->headers[] = 'Content-Transfer-Encoding: Binary';
        $rep->headers[] = 'Content-disposition: attachment; filename="' .  $fileName . '"';

        $rep->action = function () use ($fileUrl) {
            if (readfile($fileUrl) !== false) {
                unlink($fileUrl);
            }
        };
        return $rep;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): ResponseObject
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): ResponseObject
    {
        $this->headers = $headers;
        return $this;
    }

    public function getAction(): ?ClosureAlias
    {
        return $this->action;
    }

    public function setAction(?ClosureAlias $action): ResponseObject
    {
        $this->action = $action;
        return $this;
    }




}