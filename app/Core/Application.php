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
use Vereinssystem\Controllers\PlaceController;
use Vereinssystem\Controllers\DepartmentController;
use Vereinssystem\Controllers\InstallController;
use Vereinssystem\Controllers\EventController;
use Vereinssystem\Controllers\NewsController;
use Vereinssystem\Controllers\FinanceController;
use Vereinssystem\Controllers\CashController;
use Vereinssystem\Controllers\CashDocumentsController;
use Vereinssystem\Reports\{CashReportRepository,PdoCashReportRepository};
use Vereinssystem\Receipts\{ReceiptRepository,PdoReceiptRepository,ReceiptStorage};
use Vereinssystem\Cash\{CashRepository,PdoCashRepository};
use Vereinssystem\Finance\{FinanceRepository,PdoFinanceRepository};
use Vereinssystem\News\{NewsRepository,PdoNewsRepository};
use Vereinssystem\Events\{PdoEventRepository,EventRepository};
use Vereinssystem\Http\Middleware\{Authenticated,Permission,VerifyCsrf};
use Vereinssystem\Install\InstallationService;
use Vereinssystem\Places\{PdoPlaceRepository,PlaceRepository};
use Vereinssystem\Departments\{PdoDepartmentRepository,DepartmentRepository};
use Vereinssystem\Members\MemberRepository;
use Vereinssystem\Members\PdoMemberRepository;
use Vereinssystem\Repositories\PdoUserRepository;
use Vereinssystem\Repositories\UserRepository;

final class Application
{
    private readonly Router $router;
    private readonly View $views;
    private readonly bool $installed;

    private function __construct(string $root,?UserRepository $override,?MemberRepository $memberOverride,?PlaceRepository $placeOverride,?DepartmentRepository $departmentOverride,?EventRepository $eventOverride,?NewsRepository $newsOverride,?FinanceRepository $financeOverride,?CashRepository $cashOverride,?CashReportRepository $reportOverride,?ReceiptRepository $receiptOverride)
    {
        $config=new Config(require $root.'/config/app.php');
        $this->router=new Router();
        $this->views=new View($root.'/resources/views');
        $session=new Session();
        $csrf=new Csrf($session);
        $dbConfig=$root.'/config/database.local.php';
        $this->installed=is_file($dbConfig) || $override !== null; // Injected repositories represent an installed test application.
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
        $places=$placeOverride ?? ($database ? new PdoPlaceRepository($database) : null);
        $departments=$departmentOverride ?? ($database ? new PdoDepartmentRepository($database) : null);
        $memberController=new MemberController($this->views,$csrf,$auth,$members,$roles,$session,$places);
        $placeController=new PlaceController($this->views,$csrf,$auth,$places,$roles,$session);
        $departmentController=new DepartmentController($this->views,$csrf,$auth,$departments,$roles,$session);
        $events=$eventOverride ?? ($database ? new PdoEventRepository($database) : null);
        $eventController=new EventController($this->views,$csrf,$auth,$events,$roles,$session);
        // Alpha.6: News is a separate, injectable MVC domain, not an old PHP endpoint.
        $news=$newsOverride ?? ($database ? new PdoNewsRepository($database) : null);
        $newsController=new NewsController($this->views,$csrf,$auth,$news,$roles,$session);
        $finance=$financeOverride ?? ($database ? new PdoFinanceRepository($database) : null);
        // Cash and contributions share the same PDO factory and InnoDB transaction.
        $cash=$cashOverride ?? ($database ? new PdoCashRepository($database) : null);
        $financeController=new FinanceController($this->views,$csrf,$auth,$finance,$roles,$session,$cash);
        $receiptRepo=$receiptOverride ?? ($database ? new PdoReceiptRepository($database) : null);
        $reports=$reportOverride ?? ($database ? new PdoCashReportRepository($database) : null);
        $receiptStorage=new ReceiptStorage($root.'/storage/receipts');
        $cashController=new CashController($this->views,$csrf,$auth,$cash,$roles,$session,$receiptRepo);
        $documents=new CashDocumentsController($this->views,$csrf,$auth,$reports,$receiptRepo,$receiptStorage,$roles,$session);
        $installer=new InstallController($this->views,$csrf,new InstallationService($root));
        $register=require $root.'/routes/web.php';
        $register($this->router,$kernel,$authController,$accessController,$dashboard,$memberController,$placeController,$departmentController,$installer,$eventController,$newsController,$financeController,$cashController,$documents,$auth,$roles,$csrf);
    }

    public static function fromProjectRoot(string $root,?UserRepository $users=null,?MemberRepository $members=null,?PlaceRepository $places=null,?DepartmentRepository $departments=null,?EventRepository $events=null,?NewsRepository $news=null,?FinanceRepository $finance=null,?CashRepository $cash=null,?CashReportRepository $reports=null,?ReceiptRepository $receipts=null): self
    {
        return new self($root,$users,$members,$places,$departments,$events,$news,$finance,$cash,$reports,$receipts);
    }

    public function handle(Request $request): Response
    {
        try {
            // An unconfigured deployment is allowed to expose only the installer
            // and the stateless status endpoint until credentials are written.
            if (!$this->installed && !in_array($request->path,['/install','/status'],true)) {
                return Response::redirect($request->url('/install'));
            }
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
