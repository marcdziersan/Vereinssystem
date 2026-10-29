<?php
/** Alpha.9: annual balance invariants, files, exports, access and CSRF without MySQL. */
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use Vereinssystem\Auth\{Roles,User};
use Vereinssystem\Core\{Application,Request,Session};
use Vereinssystem\Models\{CashReport,Receipt};
use Vereinssystem\Reports\ReportRepository;
use Vereinssystem\Receipts\ReceiptRepository;
use Vereinssystem\Repositories\UserRepository;
use Vereinssystem\Services\{ReportService,ReceiptService};

$count=0;
function yes9(bool $test,string $label):void {global $count;if(!$test)throw new RuntimeException('FAIL '.$label);$count++;}
function fails9(callable $callback):bool
{try{$callback();return false;}catch(DomainException|RuntimeException $error){return true;}}

final class FakeReports9 implements ReportRepository
{
    public function annual(int $year,?int $accountId=null):CashReport
    {
        if($accountId!==null&&$accountId!==1)throw new DomainException('Unknown account');
        $rows=[];
        for($i=1;$i<=330;$i++)$rows[]=['id'=>$i,'account_id'=>1,'account'=>'Bank',
            'date'=>$year.'-10-01','type'=>'einnahme','amount'=>100,'category'=>'Spenden',
            'description'=>$i===1?'=HYPERLINK("evil")':($i===330?'Letzte Buchung':'Eintrag '.$i),
            'receipt'=>'B-'.$i,'void'=>$i===2,'payment_id'=>null];
        $a=['id'=>1,'name'=>'Bank','type'=>'Bank','active'=>true,'opening'=>1000,'new_opening'=>0,
            'income'=>32900,'expense'=>0,'transfer_in'=>0,'transfer_out'=>0,'closing'=>33900];
        $t=['opening'=>1000,'new_opening'=>0,'income'=>32900,'expense'=>0,'transfer_in'=>0,'transfer_out'=>0,'closing'=>33900];
        return new CashReport($year,$accountId,[$a],$rows,$t);
    }
}
$svc=new ReportService(new FakeReports9());
yes9(fails9(fn()=>$svc->annual(1999)),'Report rejects year below 2000');
yes9(fails9(fn()=>$svc->annual(2100)),'Report rejects year after 2099');
yes9(fails9(fn()=>$svc->annual(2026,0)),'Report rejects zero account');
yes9(fails9(fn()=>$svc->annual(2026,2)),'Unknown account rejected');
$report=$svc->annual(2026);
yes9(count($report->entries)===330,'All 330 entries, without 300-entry clipping');
yes9($report->totals['closing']===$report->totals['opening']+$report->totals['income'],'Closing reconciles');
$csv=$svc->csv($report);
yes9(str_starts_with($csv,"\xEF\xBB\xBF"),'CSV UTF-8 BOM');
yes9(str_contains($csv,"'\"=HYPERLINK") || str_contains($csv,"'=HYPERLINK"),'Dangerous formula neutralized');
yes9(str_contains($csv,'Letzte Buchung'),'CSV includes 330th row');
yes9(str_contains($csv,'Kontenauswertung')&&str_contains($csv,'Gesamt'),'Account summary included');
$pdf=$svc->pdf($report);
yes9(str_starts_with($pdf,'%PDF-1.4'),'Actual PDF created');
yes9(str_contains($pdf,'xref')&&str_contains($pdf,'%%EOF'),'PDF crossreference and trailer');
yes9(substr_count($pdf,'/Type /Page /Parent')>2,'PDF paginated for 330 transactions');
// Independent PDF readers must parse and extract the end of the journal.
$pdfTemp=tempnam(sys_get_temp_dir(),'vs_report9_');file_put_contents($pdfTemp,$pdf);
if(PHP_OS_FAMILY!=='Windows'&&function_exists('shell_exec')&&function_exists('exec')&&trim((string)shell_exec('command -v pdfinfo'))!==''){
    exec('pdfinfo '.escapeshellarg($pdfTemp).' 2>&1',$out,$exit);
    yes9($exit===0,'PDF accepted by external pdfinfo');
}
if(PHP_OS_FAMILY!=='Windows'&&function_exists('shell_exec')&&function_exists('exec')&&trim((string)shell_exec('command -v pdftotext'))!==''){
    exec('pdftotext '.escapeshellarg($pdfTemp).' - 2>&1',$out,$exit);
    yes9($exit===0&&str_contains(implode(' ',$out),'Letzte Buchung'),'PDF text contains last booking');
}
unlink($pdfTemp);

final class FakeReceiptRepo9 implements ReceiptRepository
{
    public ?Receipt $one=null;
    public function forEntries(array $ids):array{return [];} 
    public function byId(int $id):?Receipt{return $id===1?$this->one:null;}
    public function add(int $entryId,string $name,string $stored,string $mime,int $size,string $hash,int $actor):int{return 1;}
}
$rRepo=new FakeReceiptRepo9();$dir=dirname(__DIR__).'/storage/receipts/vs_receipt_test_'.bin2hex(random_bytes(5));mkdir($dir,0700);
$rSvc=new ReceiptService($rRepo,$dir);
yes9(ReceiptService::safeFilename('C:\\users\\Beleg.pdf')==='Beleg.pdf','No client paths used');
yes9(fails9(fn()=>ReceiptService::safeFilename('../')),'Empty/relative name rejected');
yes9(fails9(fn()=>ReceiptService::safeFilename(str_repeat('x',200))),'Long filename rejected');
$good=$dir.'/upload.pdf';file_put_contents($good,"%PDF-1.4\n1 0 obj\n<<>>\nendobj\n");
yes9(ReceiptService::detectMime($good)==='application/pdf','PDF content MIME allowed');
$bad=$dir.'/html.txt';file_put_contents($bad,'<html><script>evil()</script></html>');
yes9(fails9(fn()=>ReceiptService::detectMime($bad)),'HTML rejected regardless of extension');
yes9(fails9(fn()=>$rSvc->upload(1,['error'=>UPLOAD_ERR_OK,'tmp_name'=>$good,'size'=>filesize($good),'name'=>'Beleg.pdf'],5)),'Local non-upload rejected');
yes9(fails9(fn()=>$rSvc->upload(1,['error'=>UPLOAD_ERR_INI_SIZE],5)),'PHP upload errors rejected');
$hash=bin2hex(random_bytes(32));$raw="%PDF-1.4\naccepted\n";file_put_contents($dir.'/'.$hash,$raw);
$rRepo->one=new Receipt(1,100,'Übung.pdf',$hash,'application/pdf',strlen($raw),hash('sha256',$raw),4,'2026-10-20');
$d=$rSvc->download(1);
yes9($d['content']===$raw&&$d['filename']==='Übung.pdf','Authorized file resolver returns verified bytes');
file_put_contents($dir.'/'.$hash,$raw.'CHANGED');
yes9(fails9(fn()=>$rSvc->download(1)),'Modified receipt detected');
yes9(fails9(fn()=>$rSvc->download(100)),'Unknown receipt rejected');
$rRepo->one=new Receipt(1,100,'X','../../passwd','application/pdf',10,str_repeat('a',64),4,'2026-10-20');
yes9(fails9(fn()=>$rSvc->download(1)),'Path traversal storage name rejected');
foreach(glob($dir.'/*') as $file)@unlink($file);rmdir($dir);

$role=new Roles();
$member=new User(1,'m','1','M','M','hash',1,1);
$board=new User(4,'b','4','B','B','hash',4,1);
yes9(!$role->allows($member,'cash.report'),'Members cannot see reports');
yes9(!$role->allows($member,'cash.receipts.view'),'Members cannot download receipts');
yes9($role->allows($board,'cash.report'),'Board can see reports');
yes9($role->allows($board,'cash.receipts.add'),'Board may upload receipts');

final class FakeUsers9 implements UserRepository
{
    public function byId(int $id):?User{return match($id){1=>new User(1,'m','1','M','M','hash',1,1),4=>new User(4,'b','4','B','B','hash',4,1),default=>null};}
    public function byIdentifier(string $id):?User{return $this->byId(4);}
    public function accessList(int $limit=100):array{return [];}
    public function setRights(int $id,int $level):bool{return false;}
    public function replaceHash(int $id,string $hash):void{}
    public function recordLogin(int $id,string $ip):void{}
}
$session=new Session();$session->start();$session->forget('mvc_uid');
$app=Application::fromProjectRoot(dirname(__DIR__),new FakeUsers9());
yes9($app->handle(new Request('GET','/cash/report'))->status===303,'Anonymous report redirect');
$session->set('mvc_uid',1);
yes9($app->handle(new Request('GET','/cash/report'))->status===403,'Report cannot bypass member role');
yes9($app->handle(new Request('GET','/cash/receipt/download'))->status===403,'Receipt download cannot bypass member role');
$session->set('mvc_uid',4);
yes9($app->handle(new Request('POST','/cash/receipt/upload'))->status===419,'Receipt upload requires CSRF');
yes9($app->handle(new Request('GET','/cash/report'))->status===503,'Board report reached controller (no DB in test)');
$session->forget('mvc_uid');
$contract=file_get_contents(dirname(__DIR__).'/routes/web.php');
yes9(str_contains($contract,"'/cash/report/pdf'")&&str_contains($contract,"'/cash/receipt/download'"),'Export and receipt routes explicit');
yes9(str_contains($contract,'new VerifyCsrf($csrf)'),'Upload guarded by middleware');
$install=file_get_contents(dirname(__DIR__).'/app/Install/InstallationService.php');
yes9(str_contains($install,'007_cash_receipts.sql'),'Installer includes receipt schema');
echo "PASS $count alpha.9 report/receipt checks\n";
