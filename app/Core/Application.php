<?php
/** Composition root: create pure OOP services and register explicitly named routes. */
declare(strict_types=1);
namespace Vereinssystem\Core;

use Throwable;
use Vereinssystem\Auth\AuthService;
use Vereinssystem\Auth\LoginLimiter;
use Vereinssystem\Auth\Roles;
use Vereinssystem\Controllers\AccessController;
use Vereinssystem\Controllers\AuthController;
use Vereinssystem\Controllers\KernelController;
use Vereinssystem\Controllers\DashboardController;
use Vereinssystem\Controllers\MemberController;
use Vereinssystem\Members\MemberRepository;
use Vereinssystem\Members\PdoMemberRepository;
use Vereinssystem\Repositories\PdoUserRepository;
use Vereinssystem\Repositories\UserRepository;

final class Application
{
    private readonly Router $router;
    private readonly View $views;

    private function __construct(string $root,?UserRepository $override,?MemberRepository $memberOverride)
    {
        $config=new Config(require $root.'/config/app.php');
        $this->router=new Router();
        $this->views=new View($root.'/resources/views');
        $session=new Session();
        $csrf=new Csrf($session);
        $dbConfig=$root.'/config/database.local.php';
        $users=$override;
        $members=$memberOverride;
        $database=null;
        if ($users===null && is_file($dbConfig)) {
            $settings=require $dbConfig;
            if (!is_array($settings)) throw new DatabaseUnavailable('Invalid configuration');
            $database=new Database($settings);
            $users=new PdoUserRepository($database);
        }
        if ($members===null && $database!==null) {
            $members=new PdoMemberRepository($database);
        }
        $auth=$users ? new AuthService($users,$session,new LoginLimiter($root.'/storage/rate_limits')) : null;
        $kernel=new KernelController($this->views,$config);
        $authController=new AuthController($this->views,$csrf,$auth);
        $roles=new Roles();
        $accessController=new AccessController($this->views,$csrf,$auth,$users,$roles,$session);
        $dashboard=new DashboardController($this->views,$csrf,$auth,$members,$roles);
        $memberController=new MemberController($this->views,$csrf,$auth,$members,$roles,$session);
        $register=require $root.'/routes/web.php';
        $register($this->router,$kernel,$authController,$accessController,$dashboard,$memberController);
    }

    public static function fromProjectRoot(string $root,?UserRepository $users=null,?MemberRepository $members=null): self
    {
        return new self($root,$users,$members);
    }

    public function handle(Request $request): Response
    {
        try {
            $response=$this->router->dispatch($request);
            if ($response->status===404 || $response->status===405) {
                $html=$this->views->render('errors/status',[
                    'status'=>$response->status,
                    'message'=>$response->status===405?'Methode nicht erlaubt':'Seite nicht gefunden',
                    'url'=>$request->url('/'),'asset'=>$request->asset('css/vs-ui.css'),
                ]);
                return new Response($html,$response->status,$response->headers+['Content-Type'=>'text/html; charset=utf-8']);
            }
            return $response;
        } catch (Throwable $e) {
            error_log('[Vereinssystem/MVC] '.$e::class.': '.$e->getMessage());
            $code=$e instanceof \PDOException || $e instanceof DatabaseUnavailable ? 503 : 500;
            $html=$this->views->render('errors/status',[
                'status'=>$code,
                'message'=>$code===503?'Datenbank derzeit nicht verfügbar':'Interner Fehler',
                'url'=>$request->url('/'),'asset'=>$request->asset('css/vs-ui.css'),
            ]);
            return new Response($html,$code,['Content-Type'=>'text/html; charset=utf-8','Cache-Control'=>'no-store']);
        }
    }
}
