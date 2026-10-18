<?php
/** Immutable HTTP response object; the public entry point alone sends output. */
declare(strict_types=1);
namespace Vereinssystem\Core;
final class Response
{
    /** @param array<string,string> $headers */
    public function __construct(public readonly string $body='',public readonly int $status=200,
        public readonly array $headers=['Content-Type'=>'text/html; charset=utf-8']) {}

    public static function json(array $data,int $status=200): self
    {
        return new self((string)json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            $status,['Content-Type'=>'application/json; charset=utf-8','Cache-Control'=>'no-store']);
    }

    public static function redirect(string $url): self
    {
        // Only self-generated URLs are accepted by controllers.
        if (!str_starts_with($url,'/') || str_starts_with($url,'//') || preg_match('/[\r\n]/',$url)) {
            throw new \InvalidArgumentException('Unsafe redirect URL.');
        }
        return new self('',303,['Location'=>$url,'Cache-Control'=>'no-store']);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name=>$value) header($name.': '.$value);
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Frame-Options: DENY');
        echo $this->body;
    }
}
