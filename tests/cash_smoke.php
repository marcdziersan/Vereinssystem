<?php
/**
 * Alpha.8: isolated cash use-case assertions and HTTP middleware checks.
 * No real MySQL server is required by this deterministic smoke suite.
 */
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use Vereinssystem\Cash\CashRepository;
use Vereinssystem\Auth\{Roles,User};
use Vereinssystem\Models\{CashAccount,CashEntry};
use Vereinssystem\Finance\Money;
use Vereinssystem\Services\CashService;
use Vereinssystem\Core\{Application,Request,Session};
use Vereinssystem\Repositories\UserRepository;

$n=0;
function ok8(bool $state,string $description):void
{global $n;$n++;if(!$state)throw new RuntimeException('FAIL '.$description);}
function denied8(callable $callback):bool
{try{$callback();return false;}catch(DomainException){return true;}}

/** Fake repository implements the exact interface, with cent-based balances. */
final class DemoCash implements CashRepository
{
    public array $accountsData=[];public array $rows=[];public array $payments=[];public int $next=1;
    public function accounts(bool $includeInactive=true):array
    {
        return array_values(array_filter(array_map(function($a):CashAccount{
            $balance=$a['opening'];foreach($this->rows as $row){if($row['account']!==$a['id']||$row['void'])continue;
                $balance+=in_array($row['type'],['einnahme','umbuchung_ein'],true)?$row['cents']:-$row['cents'];}
            return new CashAccount($a['id'],$a['name'],$a['type'],$a['opening'],$a['date'],$a['active'],$balance);
        },$this->accountsData),static fn(CashAccount $a):bool=>$includeInactive||$a->active));
    }
    public function entries(int $year,?int $accountId=null):array
    {
        $result=[];foreach($this->rows as $row){if(!str_starts_with($row['date'],(string)$year)||($accountId!==null&&$row['account']!==$accountId))continue;
            $result[]=new CashEntry($row['id'],$row['account'],$this->accountsData[$row['account']]['name'],$row['type'],$row['cents'],$row['date'],$row['description'],$row['category'],$row['receipt'],$row['payment'],$row['transfer'],$row['void']);}
        return $result;
    }
    public function createAccount(string $name,string $type,int $opening,string $date,int $actor):int
    {
        foreach($this->accountsData as $a)if($a['name']===$name)throw new DomainException('Duplicate account');
        $id=count($this->accountsData)+1;$this->accountsData[$id]=compact('id','name','type','date')+['opening'=>$opening,'active'=>true];return $id;
    }
    public function setAccount(int $id,string $name,string $type,bool $active,int $actor):void
    {if(!isset($this->accountsData[$id]))throw new DomainException('Missing account');$this->accountsData[$id]['name']=$name;$this->accountsData[$id]['type']=$type;$this->accountsData[$id]['active']=$active;}
    private function put(int $account,string $type,int $cents,string $date,string $description,string $category='',string $receipt='',?int $payment=null,?string $transfer=null):int
    {if(!isset($this->accountsData[$account])||!$this->accountsData[$account]['active'])throw new DomainException('Inactive account');
        if($date<$this->accountsData[$account]['date'])throw new DomainException('Before opening');
        $id=$this->next++;$this->rows[$id]=['id'=>$id,'account'=>$account,'type'=>$type,'cents'=>$cents,'date'=>$date,'description'=>$description,'category'=>$category,'receipt'=>$receipt,'payment'=>$payment,'transfer'=>$transfer,'void'=>false];return $id;}
    public function book(int $account,string $type,int $cents,string $date,string $category,string $description,string $receipt,int $actor):int
    {return $this->put($account,$type,$cents,$date,$description,$category,$receipt);}
    public function transfer(int $from,int $to,int $cents,string $date,string $description,int $actor):array
    {if($from===$to)throw new DomainException('Same account');$key='trans-'.$this->next;$out=$this->put($from,'umbuchung_aus',$cents,$date,$description,'','',null,$key);$in=$this->put($to,'umbuchung_ein',$cents,$date,$description,'','',null,$key);return [$out,$in];}
    public function voidEntry(int $id,string $reason,int $actor):void
    {if(!isset($this->rows[$id])||$this->rows[$id]['void'])throw new DomainException('Missing');if($this->rows[$id]['payment']!==null)throw new DomainException('Linked payment');$key=$this->rows[$id]['transfer'];foreach($this->rows as &$row){if($row['id']===$id||($key!==null&&$row['transfer']===$key))$row['void']=true;}unset($row);}
    public function contribution(int $account,int $member,int $cents,string $date,string $channel,string $reference,string $note,int $actor,?int $preferred):array
    {$paymentId=count($this->payments)+1;$entry=$this->put($account,'einnahme',$cents,$date,'Beitrag #'.$member,'Mitgliedsbeitrag','BEITRAG-'.$paymentId,$paymentId);$this->payments[$paymentId]=['member'=>$member,'cents'=>$cents,'void'=>false];return ['id'=>$paymentId,'allocated'=>0,'credit'=>$cents,'cash_id'=>$entry];}
    public function voidContribution(int $paymentId,string $reason,int $actor):void
    {if(!isset($this->payments[$paymentId])||$this->payments[$paymentId]['void'])throw new DomainException('Double void');$this->payments[$paymentId]['void']=true;foreach($this->rows as &$r)if($r['payment']===$paymentId)$r['void']=true;unset($r);}
}

$repo=new DemoCash();$svc=new CashService($repo);
ok8(CashService::opening('0,00')===0,'Zero opening');
ok8(CashService::opening('-20,50')===-2050,'Negative opening');
ok8(CashService::opening('15.75')===1575,'Opening decimal');
foreach(['','NaN','1e6','-','3,123','-3,999','1.000,00'] as $bad)ok8(denied8(fn()=>CashService::opening($bad)),'Invalid opening '.$bad);
ok8(denied8(fn()=>$svc->createAccount('', 'Bank','0','2026-10-20',1)),'Account needs name');
ok8(denied8(fn()=>$svc->createAccount('Bank','','0','2026-10-20',1)),'Account needs type');
ok8(denied8(fn()=>$svc->createAccount('Bank','Bank','0','2026-02-30',1)),'Valid date');
$bank=$svc->createAccount('Bank','Bank','100,00','2026-01-01',1);
$cash=$svc->createAccount('Bar','Bargeld','50,00','2026-01-01',1);
ok8(count($svc->accounts())===2,'Two accounts');
ok8(array_sum(array_map(fn($a)=>$a->balanceCents,$svc->accounts()))===15000,'Opening 150 euro');
ok8(denied8(fn()=>$svc->createAccount('Bank','Bank','0','2026-10-20',1)),'Duplicate name');
$income=$svc->book($bank,'einnahme','20,00','2026-10-20','Spende','Spende von Verein','BE-1',1);
$expense=$svc->book($cash,'ausgabe','10,00','2026-10-20','Material','Trainingsbälle','BE-2',1);
ok8($income>0&&$expense>0,'Income and expense persisted');
ok8($svc->accounts()[0]->balanceCents===12000,'Bank income');
ok8($svc->accounts()[1]->balanceCents===4000,'Cash expense');
foreach(['umbuchung_ein','test','','UMBUCHUNG'] as $kind)ok8(denied8(fn()=>$svc->book($bank,$kind,'1,00','2026-10-20','','Test','',1)),'No forged book kind');
ok8(denied8(fn()=>$svc->book($bank,'ausgabe','0','2026-10-20','','Test','',1)),'Zero amount invalid');
ok8(denied8(fn()=>$svc->book($bank,'ausgabe','1,00','2025-12-31','','Test','',1)),'Before opening rejected');
ok8(denied8(fn()=>$svc->transfer($bank,$bank,'1,00','2026-10-20','Transfer',1)),'Distinct transfer accounts');
[$out,$in]=$svc->transfer($bank,$cash,'35,00','2026-10-20','Kasse aufgefüllt',1);
ok8($out!==$in,'Transfer has two entries');
ok8($svc->accounts()[0]->balanceCents===8500,'Transfer debits bank');
ok8($svc->accounts()[1]->balanceCents===7500,'Transfer credits cash');
ok8(array_sum(array_map(fn($a)=>$a->balanceCents,$svc->accounts()))===16000,'Transfer preserves total');
$svc->voidEntry($out,'Fehlumbuchung',1);
ok8($svc->accounts()[0]->balanceCents===12000&&$svc->accounts()[1]->balanceCents===4000,'Reversal restores both accounts');
ok8($repo->rows[$out]['void']&&$repo->rows[$in]['void'],'Both entries marked void');
ok8(denied8(fn()=>$svc->voidEntry($in,'Duplicate void',1)),'Second reversal prevented');
$payment=$svc->contribution($bank,3,'15,00','2026-10-20','Überweisung','REF-22','Monatsbeitrag',1,null);
ok8($payment['id']===1&&$payment['cash_id']>0,'Contribution linked');
ok8($svc->accounts()[0]->balanceCents===13500,'Contribution increases bank balance');
ok8(denied8(fn()=>$svc->voidEntry($payment['cash_id'],'Falsch',1)),'Cash-only void of linked payment rejected');
$svc->voidContribution($payment['id'],'Fehlbuchung',1);
ok8($repo->payments[1]['void']&&$repo->rows[$payment['cash_id']]['void'],'Joint contribution reversal');
ok8($svc->accounts()[0]->balanceCents===12000,'Joint reversal restores balance');
ok8(denied8(fn()=>$svc->voidContribution(1,'Doppelt stornieren',1)),'Payment double void rejected');
foreach(['Bitcoin',''] as $channel)ok8(denied8(fn()=>$svc->contribution($bank,3,'1,00','2026-10-20',$channel,'','',1,null)),'Channel validation');
$svc->updateAccount($cash,'Bar','Bargeld',false,1);
ok8(count($svc->accounts(false))===1,'Inactive account excluded from active list');
ok8(count($svc->accounts())===2,'Inactive account preserved in history');
ok8(denied8(fn()=>$svc->book($cash,'einnahme','1,00','2026-10-20','','Neue Spende','',1)),'Cannot book on inactive account');
ok8(count($svc->entries(2026))===5,'Journal keeps all entries');
ok8(count($svc->entries(2026,$cash))===2,'Journal account filter');

// The policy and routes enforce role and CSRF on the server, not just in buttons.
$role=new Roles();
$member=new User(3,'mit3','3','Test','Mitglied','hash',1,1);
$board=new User(4,'vorstand','4','Test','Vorstand','hash',4,1);
$admin=new User(5,'admin','5','Test','Admin','hash',5,1);
ok8(!$role->allows($member,'cash.view'),'No member cash visibility');
ok8($role->allows($board,'cash.view'),'Board may view cash');
ok8($role->allows($board,'cash.manage'),'Board may book');
ok8(!$role->allows($board,'cash.accounts'),'Only admin may create accounts');
ok8(!$role->allows($board,'cash.void'),'Only admin may void');
ok8($role->allows($admin,'cash.void'),'Admin can void');

$routes=file_get_contents(dirname(__DIR__).'/routes/web.php');
ok8(is_string($routes)&&str_contains($routes,'new VerifyCsrf($csrf)'),'CSRF route middleware configured');
ok8(str_contains($routes,"'/cash/transfer'"),'Transfer route registered');
ok8(str_contains($routes,"'/cash/void'"),'Void route registered');
$source=file_get_contents(dirname(__DIR__).'/app/Cash/PdoCashRepository.php');
ok8(is_string($source)&&str_contains($source,'beginTransaction()')&&str_contains($source,'rollBack()'),'Atomic PDO mutation wrappers');
ok8(str_contains($source,'receivePaymentInsideCurrentTransaction'),'Contribution uses shared transaction');
ok8(str_contains($source,'FOR UPDATE'),'Row locks on cash operations');
ok8(str_contains($source,'zahlung_id'),'Linked payment foreign key recorded');

// HTTP application composes an injectable cash module (without a MySQL server).
final class CashTestUsers implements UserRepository
{
    public function byId(int $id):?User{return in_array($id,[3,4,5],true)?new User($id,'user'.$id,(string)$id,'Test','Person','hash', $id===3?1:($id===4?4:5),1):null;}
    public function byIdentifier(string $identifier):?User{return $this->byId(5);}
    public function accessList(int $limit=100):array{return [$this->byId(5)];}
    public function setRights(int $id,int $level):bool{return false;}
    public function replaceHash(int $id,string $hash):void{}
    public function recordLogin(int $id,string $ip):void{}
}
$session=new Session();$session->start();$session->forget('mvc_uid');
$app=Application::fromProjectRoot(dirname(__DIR__),new CashTestUsers(),null,null,null,null,null,null,$repo);
ok8($app->handle(new Request('GET','/cash'))->status===303,'Unauthenticated cash redirects');
$session->set('mvc_uid',3);
ok8($app->handle(new Request('GET','/cash'))->status===403,'Member cash route denied');
$session->set('mvc_uid',4);
$view=$app->handle(new Request('GET','/cash'));
ok8($view->status===200,'Board cash index allowed');
ok8(str_contains($view->body,'Kassenverwaltung'),'HTML cash page rendered');
ok8($app->handle(new Request('POST','/cash/book',[],['type'=>'einnahme']))->status===419,'CSRF required on POST');
ok8($app->handle(new Request('POST','/cash/account'))->status===403,'Board cannot create accounts');
$session->set('mvc_uid',5);
ok8($app->handle(new Request('GET','/cash'))->status===200,'Admin cash page allowed');
$session->forget('mvc_uid');
echo "PASS $n alpha.8 cash assertions\n";
