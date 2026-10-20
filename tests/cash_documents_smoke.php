<?php
/**
 * Alpha.9 executable, deterministic domain/HTTP checks with isolated fakes.
 * Never mutate a real member database. PDF bytes are additionally inspected
 * with pdfinfo/pdftotext during release packaging when available.
 */
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use Vereinssystem\Auth\{User,Roles};
use Vereinssystem\Core\{Application,Request,Session};
use Vereinssystem\Repositories\UserRepository;
use Vereinssystem\Reports\{CashReportRepository,CsvExporter,PdfExporter};
use Vereinssystem\Receipts\{ReceiptStorage,ReceiptRepository};
use Vereinssystem\Models\CashReceipt;
use Vereinssystem\Services\CashReportService;
$n=0;
function check9(bool $value,string $message):void{global $n;$n++;if(!$value)throw new RuntimeException('FAIL '.$message);}
function rejected9(callable $fn):bool{try{$fn();return false;}catch(DomainException){return true;}}
final class DemoReports implements CashReportRepository
{
    public function snapshot(int $year,?int $accountId):array
    {
        $accounts=[
            ['id'=>1,'name'=>'Bank','typ'=>'Bank','aktiv'=>1,'anfangsbestand_cent'=>10000,'anfangsdatum'=>'2025-10-01','vorjahre_cent'=>2500],
            ['id'=>2,'name'=>'Kasse','typ'=>'Bargeld','aktiv'=>1,'anfangsbestand_cent'=>5000,'anfangsdatum'=>'2026-03-01','vorjahre_cent'=>0],
        ];
        $entry=static fn(int $id,int $konto,string $date,string $type,int $cents,string $description,string $category,?string $void=null):array=>
            ['id'=>$id,'konto_id'=>$konto,'konto'=>$konto===1?'Bank':'Kasse','datum'=>$date,'art'=>$type,'betrag_cent'=>$cents,
             'kategorie'=>$category,'beschreibung'=>$description,'belegnummer'=>'B-'.$id,'zahlung_id'=>null,'storniert_am'=>$void];
        $rows=[
            $entry(1,1,'2026-01-06','einnahme',1500,'Mitgliedsbeitrag','Beitrag'),
            $entry(2,1,'2026-01-12','ausgabe',800,'Material','Material'),
            $entry(3,1,'2026-03-06','umbuchung_aus',2000,'Transfer','Umbuchung'),
            $entry(4,2,'2026-03-06','umbuchung_ein',2000,'Transfer','Umbuchung'),
            $entry(5,2,'2026-03-12','ausgabe',1000,'=SUM(A1:A3)','=1+1','2026-03-12 11:01:00'),
        ];
        if($accountId!==null){$accounts=array_values(array_filter($accounts,fn($a)=>$a['id']===$accountId));$rows=array_values(array_filter($rows,fn($r)=>$r['konto_id']===$accountId));}
        return ['accounts'=>$accounts,'entries'=>$rows];
    }
}
$report=(new CashReportService(new DemoReports()))->annual(2026);
check9(count($report->accounts)===2,'Two accounts accounted');
check9($report->totals['start']===12500,'Prior-year movement is in opening balance');
check9($report->totals['new_opening']===5000,'In-year opening excluded from Jan 1');
check9($report->totals['income']===1500&&$report->totals['expense']===800,'Only valid earnings and expenses');
check9($report->totals['transfer_in']===2000&&$report->totals['transfer_out']===2000,'Double-entry transfer neutral');
check9($report->totals['end']===18200,'Annual cash balance in cents');
check9($report->totals['void_count']===1,'Voided entry counted, not balanced');
check9($report->monthly[0]['income']===1500,'Monthly income correct');
check9(count($report->categories)===2,'Category summary excludes transfer and void');
check9($report->accounts[1]['end']===7000,'New account opening and incoming transfer');
check9((new CashReportService(new DemoReports()))->annual(2026,1)->totals['end']===11200,'Account filtered closing');
check9(rejected9(fn()=>(new CashReportService(new DemoReports()))->annual(1900)),'Invalid year rejected');
check9(rejected9(fn()=>(new CashReportService(new DemoReports()))->annual(2026,-1)),'Invalid account rejected');
$csv=(new CsvExporter())->export($report);
check9(str_starts_with($csv,"\xEF\xBB\xBF"),'UTF8 BOM');
check9(str_contains($csv,'15,00'),'German currency decimals');
check9(str_contains($csv,'Storniert'),'Voided item included in export');
check9(CsvExporter::safeCell('  =HYPERLINK("x")')==="'  =HYPERLINK(\"x\")",'CSV whitespace formula injection blocked');
check9(CsvExporter::safeCell("\t@evil")==="'\t@evil",'CSV tab formula injection blocked');
check9(str_contains($csv,"'=1+1"),'CSV category formula neutralized');
$pdf=(new PdfExporter())->export($report);
check9(str_starts_with($pdf,'%PDF-1.4'),'PDF header');
check9(str_contains($pdf,'/Type /Catalog'),'PDF catalog');
check9(str_contains($pdf,'xref'),'PDF xref');
check9(str_ends_with($pdf,"%%EOF\n"),'PDF trailer');
check9(strlen($pdf)>800,'Nonempty PDF report');
$temporary=tempnam(sys_get_temp_dir(),'vs_doc_');file_put_contents($temporary,"%PDF-1.4\n1 0 obj\n<< >>\nendobj\n%%EOF\n");
$storage=new ReceiptStorage(sys_get_temp_dir().'/vs-no-http');$meta=$storage->inspect($temporary,'report.pdf',filesize($temporary));
check9($meta['mime']==='application/pdf','MIME determined from bytes');
check9(strlen($meta['sha'])===64,'SHA256 calculated');
check9(rejected9(fn()=>$storage->inspect($temporary,"in\nvalid.pdf",filesize($temporary))),'CRLF names rejected');
check9(rejected9(fn()=>$storage->inspect($temporary,'ok.pdf',6_000_000)),'Oversized upload rejected');
check9(rejected9(fn()=>$storage->storeUploaded($temporary)),'Fabricated local upload rejected');
check9(rejected9(fn()=>$storage->read('../config/database.local.php',$meta['sha'])),'Path traversal rejected');
unlink($temporary);
check9((new Roles())->allows(new User(5,'a','1','A','B','x',5,1),'cash.export'),'Admin export capability');
check9(!(new Roles())->allows(new User(1,'a','1','A','B','x',1,1),'cash.receipts.view'),'Member cannot view receipts');
final class DocUsers implements UserRepository
{
    public function byId(int $id):?User{return in_array($id,[1,4,5],true)?new User($id,'member'.$id,(string)$id,'A','B','hash',$id,1):null;}
    public function byIdentifier(string $username):?User{return null;}
    public function accessList(int $limit=100):array{return [];}
    public function setRights(int $id,int $level):bool{return false;}
    public function replaceHash(int $id,string $newHash):void{}
    public function recordLogin(int $id,string $ip):void{}
}
final class DocReceipts implements ReceiptRepository
{
    public function forEntries(array $ids):array{return [];}
    public function find(int $id):?CashReceipt{return null;}
    public function attach(int $id,string $o,string $m,int $size,string $sha,string $st,int $actor):int{return 1;}
}
$s=new Session();$s->start();$s->forget('mvc_uid');
$app=Application::fromProjectRoot(dirname(__DIR__),new DocUsers(),null,null,null,null,null,null,null,new DemoReports(),new DocReceipts());
check9($app->handle(new Request('GET','/cash/reports'))->status===303,'Login required for reports');
$s->set('mvc_uid',1);
check9($app->handle(new Request('GET','/cash/reports'))->status===403,'Member report denied');
check9($app->handle(new Request('GET','/cash/export/pdf'))->status===403,'Member PDF denied');
check9($app->handle(new Request('GET','/cash/receipts/download'))->status===403,'Member receipt denied');
$s->set('mvc_uid',4);
$r=$app->handle(new Request('GET','/cash/reports'));
check9($r->status===200&&str_contains($r->body,'Jahresübersicht'),'Board report displays');
$csvResp=$app->handle(new Request('GET','/cash/export/csv'));
check9($csvResp->status===200&&str_contains($csvResp->headers['Content-Type'],'csv'),'CSV export route');
$pdfResp=$app->handle(new Request('GET','/cash/export/pdf'));
check9($pdfResp->status===200&&str_starts_with($pdfResp->body,'%PDF-'),'PDF export route');
check9($app->handle(new Request('POST','/cash/receipts/upload',[],['entry'=>'1']))->status===419,'CSRF middleware blocks upload');
check9($app->handle(new Request('GET','/cash/receipts/download', ['id'=>'1']))->status===404,'Unknown receipt not exposed');
check9($app->handle(new Request('GET','/cash/reports',['year'=>'1980']))->status===422,'Invalid year 422');
$s->forget('mvc_uid');
file_put_contents('/tmp/vs-alpha9-test-report.pdf',$pdf);
fwrite(STDOUT,"PASS $n alpha.9 documents assertions\n");
