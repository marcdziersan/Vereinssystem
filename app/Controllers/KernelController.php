<?php
/** Public health endpoint; never discloses account, database or secrets. */
declare(strict_types=1);
namespace Vereinssystem\Controllers;
use Vereinssystem\Core\Config;
use Vereinssystem\Core\Request;
use Vereinssystem\Core\Response;
use Vereinssystem\Core\View;
final class KernelController
{
    public function __construct(private readonly View $views,private readonly Config $config) {}
    public function status(Request $request): Response
    { return Response::json(['application'=>'Vereinssystem MVC','version'=>$this->config->requireString('version'),'status'=>'ok']); }
}
