<?php
/** HTTP adapter for cash-account and booking use cases. Authorization is route middleware. */
declare(strict_types=1);
namespace Vereinssystem\Controllers;

use Vereinssystem\Auth\{AuthService,Roles,User};
use Vereinssystem\Cash\CashRepository;
use Vereinssystem\Receipts\ReceiptRepository;
use Vereinssystem\Core\{Csrf,DatabaseUnavailable,Request,Response,Session,View};
use Vereinssystem\Finance\Money;
use Vereinssystem\Services\CashService;

final class CashController extends Controller
{
    public function __construct(View $views,Csrf $csrf,private readonly ?AuthService $auth,
        private readonly ?CashRepository $repository,private readonly Roles $roles,private readonly Session $session,
        private readonly ?ReceiptRepository $receipts=null)
    {parent::__construct($views,$csrf);}
    private function user():User{return $this->auth?->currentUser()??throw new \LogicException('Middleware fehlt.');}
    private function service():CashService{return new CashService($this->repository??throw new DatabaseUnavailable('Kasse nicht verfügbar.'));}
    private static function id(string $value):int
    {$n=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return is_int($n)?$n:0;}
    private function flash():string
    {$v=$this->session->get('cash_flash','');$this->session->forget('cash_flash');return is_string($v)?$v:'';}
    public function index(Request $request):Response
    {
        $user=$this->user();$year=filter_var($request->queryString('year',(string)date('Y')),FILTER_VALIDATE_INT);
        $year=is_int($year)&&$year>=2000&&$year<=2099?$year:(int)date('Y');
        $account=self::id($request->queryString('account'))?:null;
        $service=$this->service();
        $entries=$service->entries($year,$account);
        $receipts=$this->receipts ? $this->receipts->forEntries(array_map(static fn($e)=>$e->id,$entries)) : [];
        return $this->page($request,'Vereinskasse','cash/index',[
            'accounts'=>$service->accounts(),'entries'=>$entries,
            'receipts'=>$receipts,'canReceipts'=>$this->roles->allows($user,'cash.receipts.add'),
            'receiptUploadUrl'=>$request->url('/cash/receipt/upload'),
            'receiptDownloadUrl'=>$request->url('/cash/receipt/download'),
            'reportUrl'=>$request->url('/cash/report'),
            'year'=>$year,'accountFilter'=>$account,'flash'=>$this->flash(),'csrfToken'=>$this->csrf->token(),
            'canManage'=>$this->roles->allows($user,'cash.manage'),'canAccounts'=>$this->roles->allows($user,'cash.accounts'),
            'canVoid'=>$this->roles->allows($user,'cash.void'),'baseUrl'=>$request->url('/cash'),
            'bookUrl'=>$request->url('/cash/book'),'accountUrl'=>$request->url('/cash/account'),
            'updateAccountUrl'=>$request->url('/cash/account/update'),'transferUrl'=>$request->url('/cash/transfer'),
            'voidUrl'=>$request->url('/cash/void'),
        ],$user);
    }
    public function action(Request $request):Response
    {
        $user=$this->user();$service=$this->service();
        try{
            $message=match($request->path){
                '/cash/account'=> 'Konto #'.$service->createAccount($request->inputString('name'),$request->inputString('type'),
                    $request->inputString('opening','0'),$request->inputString('date'),$user->id).' angelegt.',
                '/cash/account/update'=> $this->updateAccount($request,$service,$user->id),
                '/cash/book'=>'Buchung #'.$service->book(self::id($request->inputString('account')),$request->inputString('type'),
                    $request->inputString('amount'),$request->inputString('date'),$request->inputString('category'),
                    $request->inputString('description'),$request->inputString('receipt'),$user->id).' gespeichert.',
                '/cash/transfer'=> $this->transfer($request,$service,$user->id),
                '/cash/void'=> $this->void($request,$service,$user->id),
                default=>throw new \DomainException('Unbekannte Kassenaktion.'),
            };
        }catch(\DomainException $e){$message='Hinweis: '.$e->getMessage();}
        $this->session->set('cash_flash',$message);
        return Response::redirect($request->url('/cash'));
    }
    private function transfer(Request $req,CashService $service,int $actor):string
    {
        [$out,$in]=$service->transfer(self::id($req->inputString('from')),self::id($req->inputString('to')),
            $req->inputString('amount'),$req->inputString('date'),$req->inputString('description'),$actor);
        return 'Umbuchung #'.$out.' / #'.$in.' gespeichert.';
    }
    private function updateAccount(Request $req,CashService $service,int $actor):string
    {
        $service->updateAccount(self::id($req->inputString('id')),$req->inputString('name'),$req->inputString('type'),
            $req->inputString('active')==='1',$actor);
        return 'Konto aktualisiert.';
    }
    private function void(Request $req,CashService $service,int $actor):string
    {
        $service->voidEntry(self::id($req->inputString('id')),$req->inputString('reason'),$actor);
        return 'Buchung beziehungsweise Umbuchung storniert.';
    }
}
