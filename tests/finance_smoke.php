<?php
/** Alpha.7 ledger calculations, validation, role middleware and HTML rendering. */
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Vereinssystem\Auth\{Roles,User};
use Vereinssystem\Core\{Application,Request,Session};
use Vereinssystem\Finance\{FinanceRepository,Money};
use Vereinssystem\Models\{Claim,Payment,ContributionRate};
use Vereinssystem\Repositories\UserRepository;
use Vereinssystem\Services\FinanceService;
$checks=0;
function ok(bool $valid,string $description):void {global $checks;$checks++;if(!$valid)throw new RuntimeException('FAIL '.$description);}
function denied(callable $callback):bool{try{$callback();return false;}catch(DomainException $e){return true;}}
/** Deterministic, isolated in-memory account for use-case checks. */
final class FakeLedger implements FinanceRepository
{
    public array $charges=[];public array $payments=[];public array $tariffs=[];
    public function statement(int $memberId,int $year):array{
        $claimRows=[];$paymentRows=[];$soll=0;$ist=0;$incoming=0;$used=0;
        foreach($this->charges as $f){if($f['mitglied_id']!==$memberId)continue;
            $paid=0;foreach($this->payments as $z){if($z['mitglied_id']===$memberId&&$z['storniert_am']===null)$paid+=$z['parts'][$f['id']]??0;}
            $f['bezahlt_cent']=$paid;
            if(str_starts_with($f['periode'],(string)$year)){
                $claimRows[]=$f;if($f['storniert_am']===null){$soll+=$f['betrag_cent'];$ist+=$paid;}
            }
        }
        foreach($this->payments as $z){if($z['mitglied_id']!==$memberId)continue;
            if($z['storniert_am']===null){$incoming+=$z['betrag_cent'];$used+=array_sum($z['parts']);}
            if(str_starts_with($z['gezahlt_am'],(string)$year)){
                $copy=$z;$copy['zugeordnet_cent']=array_sum($z['parts']);$paymentRows[]=$copy;
            }
        }
        return ['member'=>['mitglieder_id'=>$memberId,'vorname'=>'Max','nachname'=>'Testmann'],
            'claims'=>$claimRows,'payments'=>$paymentRows,'totals'=>['soll'=>$soll,'ist'=>$ist,'offen'=>max(0,$soll-$ist),'guthaben'=>$incoming-$used]];
    }
    public function rates():array{return $this->tariffs;}
    public function setRate(string $kind,int $departmentId,string $date,int $cents,int $actor):void{
        $key=$kind.'-'.$departmentId.'-'.$date;if(isset($this->tariffs[$key]))throw new DomainException('Tarif vorhanden');
        $this->tariffs[$key]=['id'=>count($this->tariffs)+1,'art'=>$kind,'abteilung_id'=>$departmentId,'gueltig_ab'=>$date,'betrag_cent'=>$cents];
    }
    public function createClaim(int $member,string $period,string $kind,int $department,string $description,int $cents,string $due,int $actor):int{
        foreach($this->charges as $old)if($old['mitglied_id']===$member&&$old['periode']===$period&&$old['art']===$kind&&$old['abteilung_id']===$department)throw new DomainException('Duplicate claim');
        $id=count($this->charges)+1;$this->charges[$id]=['id'=>$id,'mitglied_id'=>$member,'periode'=>$period,'art'=>$kind,'abteilung_id'=>$department,'beschreibung'=>$description,'betrag_cent'=>$cents,'faellig_am'=>$due,'storniert_am'=>null];return $id;
    }
    public function generateMonth(string $period,int $actor):array{return ['new'=>0,'existing'=>count($this->charges),'cent'=>0];}
    public function receivePayment(int $member,int $cents,string $date,string $channel,string $reference,string $note,int $actor,?int $preferred):array{
        $id=count($this->payments)+1;$remaining=$cents;$parts=[];
        $claims=$this->charges;
        if($preferred!==null){if(!isset($claims[$preferred])||$claims[$preferred]['mitglied_id']!==$member)throw new DomainException('Foreign claim');uksort($claims,fn($a,$b)=>$a===$preferred?-1:($b===$preferred?1:0));}
        foreach($claims as $claim){if($claim['mitglied_id']!==$member||$claim['storniert_am']!==null)continue;
            $allocated=0;foreach($this->payments as $z)if($z['storniert_am']===null)$allocated+=$z['parts'][$claim['id']]??0;
            $part=min($remaining,max(0,$claim['betrag_cent']-$allocated));if($part){$parts[$claim['id']]=$part;$remaining-=$part;}
        }
        $this->payments[$id]=['id'=>$id,'mitglied_id'=>$member,'betrag_cent'=>$cents,'gezahlt_am'=>$date,'zahlungsweg'=>$channel,'referenz'=>$reference,'bemerkung'=>$note,'storniert_am'=>null,'parts'=>$parts];
        return ['id'=>$id,'allocated'=>$cents-$remaining,'credit'=>$remaining];
    }
    public function reconcile(int $member):int{
        $sum=0;foreach($this->payments as &$z){if($z['mitglied_id']!==$member||$z['storniert_am']!==null)continue;
            $available=$z['betrag_cent']-array_sum($z['parts']);
            foreach($this->charges as $f){if($f['mitglied_id']!==$member||$f['storniert_am']!==null)continue;
                $used=0;foreach($this->payments as $other)if($other['storniert_am']===null)$used+=$other['parts'][$f['id']]??0;
                $part=min($available,max(0,$f['betrag_cent']-$used));if($part){$z['parts'][$f['id']]=($z['parts'][$f['id']]??0)+$part;$available-=$part;$sum+=$part;}}
        }unset($z);return $sum;
    }
    public function voidPayment(int $id,string $reason,int $actor):void{if(!isset($this->payments[$id])||$this->payments[$id]['storniert_am']!==null)throw new DomainException('Not found');$this->payments[$id]['storniert_am']='now';}
    public function voidClaim(int $id,string $reason,int $actor):void{
        if(!isset($this->charges[$id]))throw new DomainException('Not found');
        foreach($this->payments as $p)if($p['storniert_am']===null&&isset($p['parts'][$id]))throw new DomainException('Paid claim cannot be voided');
        $this->charges[$id]['storniert_am']='now';
    }
}
final class FakeAccounts implements UserRepository
{
    public function byId(int $id):?User{static $hash=null;$hash??=password_hash('secret123456789',PASSWORD_DEFAULT);return match($id){1,2,3=>new User($id,'user'.$id,'M'.$id,'Max','Testmann',$hash,match($id){1=>5,2=>4,3=>1},1),default=>null};}
    public function byIdentifier(string $value):?User{return $this->byId(1);}
    public function accessList(int $limit=100):array{return [$this->byId(1)];}
    public function setRights(int $id,int $level):bool{return false;}
    public function replaceHash(int $id,string $hash):void{}
    public function recordLogin(int $id,string $ip):void{}
}
$repo=new FakeLedger();$s=new FinanceService($repo);
ok(Money::parse('15,00')===1500,'Decimal euros parsed');
ok(Money::parse('0.01')===1,'One cent');
ok(Money::parse('0,00',true)===0,'Explicit zero tariff allowed');
ok(Money::parse('1000000,00')===100000000,'Maximum');
ok(Money::format(-105)==='-1,05 €','Signed formatting');
ok(Money::format(1234567)==='12.345,67 €','No float formatting');
foreach(['','-1','1.000,00','1e5','1,234','0','1000001','15,00€','01,20'] as $invalid)ok(denied(fn()=>Money::parse($invalid)),'Rejected amount '.$invalid);
ok(denied(fn()=>FinanceService::period('2026-13')),'Invalid month');
ok(denied(fn()=>FinanceService::date('2026-02-30')),'Invalid date');
ok(denied(fn()=>FinanceService::year(1999)),'Invalid year');
ok(denied(fn()=>FinanceService::member(0)),'Invalid member');
ok(denied(fn()=>$s->setRate('bonus',0,'2026-01-01','1,00',1)),'Tariff kind');
ok(denied(fn()=>$s->setRate('abteilung',0,'2026-01-01','1,00',1)),'Department required');
$s->setRate('erwachsen',0,'2026-01-01','15,00',1);
ok(count($s->rates())===1,'Tariff stored');
$s->setRate('abteilung',7,'2026-12-01','0,00',1);
ok($s->rates()['abteilung-7-2026-12-01']['betrag_cent']===0,'Zero tariff stops future department dues');
ok(denied(fn()=>$s->setRate('erwachsen',0,'2026-01-01','99,00',1)),'Historical rate immutable');
$charge=$s->charge(3,'2026-10','grundbeitrag',0,'Monatsbeitrag','15,00','2026-10-31',1);
ok($charge===1,'Monthly claim created');
ok(denied(fn()=>$s->charge(3,'2026-10','grundbeitrag',0,'Monatsbeitrag','15,00','2026-10-31',1)),'Duplicate rejected');
$first=$s->payment(3,'10,00','2026-10-10','Überweisung','A1','',1);
ok($first['allocated']===1000&&$first['credit']===0,'Partial allocation 10/15');
$statement=$s->statement(3,2026);
ok($statement['totals']['soll']===1500&&$statement['totals']['ist']===1000&&$statement['totals']['offen']===500,'Soll/Ist/open remainder 5 EUR');
ok(denied(fn()=>$s->voidClaim(1,'Incorrect claim',1)),'No void of paid claim');
$second=$s->payment(3,'7,00','2026-10-11','Bar','A2','',1);
ok($second['allocated']===500&&$second['credit']===200,'Remaining claim and new credit');
$statement=$s->statement(3,2026);
ok($statement['totals']['offen']===0&&$statement['totals']['guthaben']===200,'Credit preserved');
$secondClaim=$s->charge(3,'2026-11','grundbeitrag',0,'Novemberbeitrag','1,00','2026-11-30',1);
ok($s->reconcile(3)===100,'Credit offset later due');
ok($s->reconcile(3)===0,'Reconcile idempotent');
ok($s->statement(3,2026)['totals']['guthaben']===100,'Remaining credit 1 EUR');
$s->voidPayment($first['id'],'Falscher Zahlungseingang',1);
ok($s->statement(3,2026)['totals']['offen']===1000,'Void reopens claims');
ok(denied(fn()=>$s->voidPayment($first['id'],'Double void',1)),'Double void rejected');
ok(denied(fn()=>$s->payment(3,'1,00','2026-01-01','Bitcoin','x','',1)),'Unknown payment channel rejected');
$exampleClaim=new Claim(1,3,'2026-10','grundbeitrag','Betrag',1500,1000,'2026-10-31',false);
ok($exampleClaim->openCents()===500,'Claim entity balance');
$examplePayment=new Payment(1,3,700,500,'2026-10-11','Bar','A',false);
ok($examplePayment->creditCents()===200,'Payment entity credit');
$rate=new ContributionRate(1,'erwachsen',0,'2026-01-01',1500);
ok($rate->cents===1500,'Tariff entity immutable fields');
ok(Roles::permissions()['finance.view']===1,'Member own account right');
ok(Roles::permissions()['finance.manage']===4,'Board booking right');
ok(Roles::permissions()['finance.tariffs']===5,'Admin tariff right');
ok(Roles::permissions()['finance.void']===5,'Admin cancel right');
$session=new Session();$session->start();$_SESSION=[];
$app=Application::fromProjectRoot(dirname(__DIR__),new FakeAccounts(),null,null,null,null,null,$repo);
$get=fn(string $path,array $query=[]):Request=>new Request('GET',$path,$query);
$post=fn(string $path,array $fields=[]):Request=>new Request('POST',$path,[],$fields);
ok($app->handle($get('/finance'))->status===303,'Anonymous redirected');
$_SESSION['mvc_uid']=3;
ok($app->handle($get('/finance'))->status===200,'Member own account');
ok($app->handle($get('/finance',['member'=>'2']))->status===403,'Member cannot read foreign contributions');
ok($app->handle($get('/finance/tariffs'))->status===403,'Member cannot access admin rates');
ok($app->handle($post('/finance/payment',['member'=>'3']))->status===403,'Member cannot book');
$_SESSION['mvc_uid']=2;
ok($app->handle($get('/finance',['member'=>'3']))->status===200,'Board may view another member');
ok($app->handle($get('/finance/tariffs'))->status===403,'Board cannot change tariffs');
ok($app->handle($post('/finance/payment',['member'=>'3']))->status===419,'Board POST needs CSRF');
$token=(string)$_SESSION['_vs_csrf'];
ok($app->handle($post('/finance/payment',['_csrf'=>$token,'member'=>'3','amount'=>'2,00','date'=>'2026-10-18','channel'=>'Bar']))->status===303,'Board payment POST');
ok($app->handle($post('/finance/void-claim',['_csrf'=>$token,'id'=>'1']))->status===403,'Board cannot void');
$_SESSION['mvc_uid']=1;
ok($app->handle($get('/finance/tariffs'))->status===200,'Admin tariff page');
ok($app->handle($post('/finance/rate',['_csrf'=>$token,'kind'=>'kind','department'=>'0','effective'=>'2026-01-01','amount'=>'4,00']))->status===303,'Admin saves rate');
ok(count($repo->tariffs)===3,'Tariff via HTTP');
ok($app->handle($post('/finance/generate',['_csrf'=>$token,'period'=>'2026-10']))->status===303,'Admin generates period');
$moneyView=$app->handle($get('/finance',['member'=>'3']))->body;
ok(str_contains($moneyView,'Beitragskonto')&&str_contains($moneyView,'Soll')&&str_contains($moneyView,'Zahlungen'),'Ledger view renders');
$schema=file_get_contents(dirname(__DIR__).'/database/migrations/005_finance.sql');
ok(substr_count($schema,'CREATE TABLE IF NOT EXISTS')===4,'Four compatible contribution tables');
ok(str_contains(file_get_contents(dirname(__DIR__).'/app/Install/InstallationService.php'),'005_finance.sql'),'Browser installer includes finance');
ok(is_file(dirname(__DIR__).'/bin/migrate-finance.php'),'Existing databases have migration tool');
ok(!is_file(dirname(__DIR__).'/finanzen.php'),'No historical endpoint');
echo "PASS {$checks} alpha.7 finance assertions\n";
