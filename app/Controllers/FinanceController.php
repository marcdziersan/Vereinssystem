<?php
/** Finance HTTP adapter: auth context, safe input, redirects and mobile templates only. */
declare(strict_types=1);
namespace Vereinssystem\Controllers;
use Vereinssystem\Auth\{AuthService,Roles,User};
use Vereinssystem\Core\{Csrf,DatabaseUnavailable,Request,Response,Session,View};
use Vereinssystem\Finance\{FinanceRepository,Money};
use Vereinssystem\Cash\CashRepository;
use Vereinssystem\Services\CashService;
use Vereinssystem\Services\FinanceService;
final class FinanceController extends Controller
{
    public function __construct(View $views,Csrf $csrf,private readonly ?AuthService $auth,
        private readonly ?FinanceRepository $repository,private readonly Roles $roles,private readonly Session $session,
        private readonly ?CashRepository $cash=null)
    {parent::__construct($views,$csrf);}
    private function user():User {return $this->auth?->currentUser()??throw new \LogicException('Authentifizierungs-Middleware fehlt.');}
    private function service():FinanceService {return new FinanceService($this->repository??throw new DatabaseUnavailable('Finanzdatenbank nicht verfügbar.'));}
    private static function id(string $value):int
    { $n=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return is_int($n)?$n:0; }
    private function flash():string
    {$msg=$this->session->get('finance_flash','');$this->session->forget('finance_flash');return is_string($msg)?$msg:'';}
    private function memberFor(Request $request,bool $post):int
    {
        $member=self::id($post?$request->inputString('member'):$request->queryString('member'));
        $user=$this->user();$member=$member?:$user->id;
        if($member!==$user->id && !$this->roles->allows($user,'finance.manage'))throw new \DomainException('Nur eigene Beiträge dürfen eingesehen werden.');
        return $member;
    }
    public function index(Request $request):Response
    {
        $user=$this->user();
        try{$member=$this->memberFor($request,false);}
        catch(\DomainException){return $this->forbidden($request);}
        $year=filter_var($request->queryString('year',(string)date('Y')),FILTER_VALIDATE_INT);
        $year=is_int($year)&&$year>=2000&&$year<=2099?$year:(int)date('Y');
        $data=$this->service()->statement($member,$year);
        return $this->page($request,'Beitragskonto','finance/index',[
            'ledger'=>$data,'memberId'=>$member,'year'=>$year,'flash'=>$this->flash(),
            'cashAccounts'=>$this->cash?(new CashService($this->cash))->accounts(false):[],
            'canManage'=>$this->roles->allows($user,'finance.manage'),
            'canVoid'=>$this->roles->allows($user,'finance.void'),
            'canGenerate'=>$this->roles->allows($user,'finance.generate'),
            'csrfToken'=>$this->csrf->token(),'url'=>$request->url('/finance'),
            'paymentUrl'=>$request->url('/finance/payment'),'claimUrl'=>$request->url('/finance/claim'),
            'reconcileUrl'=>$request->url('/finance/reconcile'),'voidPaymentUrl'=>$request->url('/finance/void-payment'),
            'voidClaimUrl'=>$request->url('/finance/void-claim'),'generateUrl'=>$request->url('/finance/generate'),
            'tariffsUrl'=>$request->url('/finance/tariffs'),
        ],$user);
    }
    public function tariffs(Request $request):Response
    {
        return $this->page($request,'Beitragssätze','finance/tariffs',[
            'rates'=>$this->service()->rates(),'csrfToken'=>$this->csrf->token(),'flash'=>$this->flash(),
            'saveUrl'=>$request->url('/finance/rate'),'backUrl'=>$request->url('/finance'),
        ],$this->user());
    }
    /** A POST route is protected by role AND CSRF middleware; services validate domain inputs. */
    public function action(Request $request):Response
    {
        $path=$request->path;$user=$this->user();
        $member=self::id($request->inputString('member'));
        $year=$request->inputString('year',(string)date('Y'));
        try{
            $service=$this->service();
            switch($path){
                case '/finance/payment':
                    $result=$this->cash
                        ?(new CashService($this->cash))->contribution(self::id($request->inputString('account')),$member,
                            $request->inputString('amount'),$request->inputString('date'),$request->inputString('channel'),
                            $request->inputString('reference'),$request->inputString('note'),$user->id,
                            self::id($request->inputString('claim'))?:null)
                        :$service->payment($member,$request->inputString('amount'),$request->inputString('date'),
                            $request->inputString('channel'),$request->inputString('reference'),$request->inputString('note'),
                            $user->id,self::id($request->inputString('claim'))?:null);
                    $message='Zahlung #'.$result['id'].' erfasst: '.Money::format($result['allocated']).' zugeordnet, '.Money::format($result['credit']).' Guthaben.';break;
                case '/finance/claim':
                    $claimId=$service->charge($member,$request->inputString('period'),$request->inputString('kind'),
                        self::id($request->inputString('department')),$request->inputString('description'),
                        $request->inputString('amount'),$request->inputString('due'),$user->id);
                    $message='Forderung #'.$claimId.' angelegt.';break;
                case '/finance/reconcile':
                    $amount=$service->reconcile($member);$message=Money::format($amount).' Guthaben zugeordnet.';break;
                case '/finance/void-payment':
                    if($this->cash)(new CashService($this->cash))->voidContribution(self::id($request->inputString('id')),$request->inputString('reason'),$user->id);
                    else $service->voidPayment(self::id($request->inputString('id')),$request->inputString('reason'),$user->id);
                    $message='Zahlung storniert.';break;
                case '/finance/void-claim':
                    $service->voidClaim(self::id($request->inputString('id')),$request->inputString('reason'),$user->id);
                    $message='Forderung storniert.';break;
                case '/finance/generate':
                    $result=$service->generateMonth($request->inputString('period'),$user->id);
                    $message=$result['new'].' Sollstellungen angelegt, '.$result['existing'].' vorhanden ('.Money::format($result['cent']).').';break;
                case '/finance/rate':
                    $service->setRate($request->inputString('kind'),self::id($request->inputString('department')),
                        $request->inputString('effective'),$request->inputString('amount'),$user->id);
                    $message='Neuer historischer Beitragssatz gespeichert.';break;
                default: throw new \DomainException('Unbekannte Aktion.');
            }
        }catch(\DomainException $e){$message='Hinweis: '.$e->getMessage();}
        $this->session->set('finance_flash',$message);
        if($path==='/finance/rate')return Response::redirect($request->url('/finance/tariffs'));
        $url=$request->url('/finance');
        if($member>0)$url.='&member='.$member;
        if(preg_match('/^20[0-9]{2}$/D',$year))$url.='&year='.$year;
        return Response::redirect($url);
    }
}
