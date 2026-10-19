<?php
/** Normalized HTTP request; controllers do not access POST/GET superglobals. */
declare(strict_types=1);
namespace Vereinssystem\Core;
final class Request
{
    /** @param array<string,mixed> $query @param array<string,mixed> $body */
    public function __construct(public readonly string $method,public readonly string $path,
        private readonly array $query=[],private readonly array $body=[],
        public readonly string $scriptName='/index.php',public readonly string $remoteIp='127.0.0.1') {}

    public static function fromGlobals(): self
    {
        $method=strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'));
        $script=(string)($_SERVER['SCRIPT_NAME']??'/index.php');
        $forced=$_GET['r']??null;
        if (is_string($forced)) $path=$forced;
        else {
            $rawPath=parse_url((string)($_SERVER['REQUEST_URI']??'/'),PHP_URL_PATH);
            $path=is_string($rawPath)?rawurldecode($rawPath):'/';
            $base=rtrim(str_replace('\\','/',dirname($script)),'/');
            // Apache/WAMP may internally rewrite /verein/* to /verein/public/index.php.
            // REQUEST_URI keeps the original /verein/* URL, so strip the project base.
            if (str_ends_with($base,'/public')) $base=substr($base,0,-7);
            if($base!==''&&$base!=='.'&&str_starts_with($path,$base.'/'))$path=substr($path,strlen($base));
            if($path==='/index.php')$path='/';
        }
        return new self($method,self::normalizePath($path),$_GET,$_POST,$script,
            (string)($_SERVER['REMOTE_ADDR']??'127.0.0.1'));
    }

    public static function normalizePath(string $path): string
    {
        if($path==='/')return '/';
        return preg_match('~^/(?:[a-z0-9-]+/)*[a-z0-9-]+/?$~D',$path)?rtrim($path,'/'):'/__invalid-route';
    }
    public function queryString(string $key,string $fallback=''): string
    { $v=$this->query[$key]??$fallback; return is_string($v)?$v:$fallback; }
    public function inputString(string $key,string $fallback=''): string
    { $v=$this->body[$key]??$fallback; return is_string($v)?$v:$fallback; }
    /**
     * Obtain a multi-select list without interpreting browser input as SQL or PHP code.
     * Invalid nested values remain visible to the domain validator and are rejected there.
     * @return array<mixed>
     */
    public function inputList(string $key): array
    {
        $input = $this->body[$key] ?? [];
        return is_array($input) ? array_values($input) : [];
    }

    public function url(string $route='/'): string
    {
        // Root .htaccess deployment: link to /verein/?r=... rather than /verein/public/index.php.
        $base=rtrim(str_replace('\\','/',dirname($this->scriptName)),'/');
        if (str_ends_with($base,'/public')) return substr($base,0,-7).'/?r='.rawurlencode(self::normalizePath($route));
        return $this->scriptName.'?r='.rawurlencode(self::normalizePath($route));
    }
    public function asset(string $relativePath): string
    {
        if(!preg_match('~^[a-z0-9][a-z0-9/._-]*$~iD',$relativePath)||str_contains($relativePath,'..'))
            throw new \InvalidArgumentException('Invalid public asset path.');
        $base=rtrim(str_replace('\\','/',dirname($this->scriptName)),'/');
        return ($base==='.'?'':$base).'/assets/'.$relativePath;
    }
}
