<?php
/** HTTP boundary for annual reports, downloads and receipt file uploads. */
declare(strict_types=1);
namespace Vereinssystem\Controllers;
use Vereinssystem\Auth\{AuthService,Roles,User};
use Vereinssystem\Core\{Csrf,DatabaseUnavailable,Request,Response,Session,View};
use Vereinssystem\Reports\{CashReportRepository,CsvExporter,PdfExporter};
use Vereinssystem\Receipts\{ReceiptRepository,ReceiptStorage};
use Vereinssystem\Services\{CashReportService,ReceiptService};
final class CashDocumentsController extends Controller
{
    public function __construct(View $views,Csrf $csrf,private readonly ?AuthService $auth,
        private readonly ?CashReportRepository $reports,private readonly ?ReceiptRepository $receipts,
        private readonly ReceiptStorage $storage,private readonly Roles $roles,private readonly Session $session)
    {parent::__construct($views,$csrf);}

    private function user():User{return $this->auth?->currentUser()??throw new \LogicException('Authentifizierung fehlt.');}
    private function reporting():CashReportService{return new CashReportService($this->reports??throw new DatabaseUnavailable('Kassenbericht nicht verfügbar.'));}
    private function attachments():ReceiptService{return new ReceiptService($this->receipts??throw new DatabaseUnavailable('Belege nicht verfügbar.'),$this->storage);}
    private static function positive(string $value):int
    {
        $v=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        return is_int($v)?$v:0;
    }
    private function selection(Request $req):array
    {
        $year=filter_var($req->queryString('year',(string)date('Y')),FILTER_VALIDATE_INT);
        if(!is_int($year))$year=0;
        $account=$req->queryString('account');
        $id=$account===''?null:self::positive($account);
        return [$year,$id];
    }
    public function reports(Request $req):Response
    {
        try{
            [$year,$account]=$this->selection($req);
            $report=$this->reporting()->annual($year,$account);
        }catch(\DomainException $e){return new Response('Ungültiger Berichtsfilter.',422,['Content-Type'=>'text/plain; charset=utf-8','Cache-Control'=>'no-store']);}
        return $this->page($req,'Kassenberichte','cash/reports',[
            'report'=>$report,'reportUrl'=>$req->url('/cash/reports'),
            'csvUrl'=>$req->url('/cash/export/csv'),'pdfUrl'=>$req->url('/cash/export/pdf'),
            'backUrl'=>$req->url('/cash'),
        ],$this->user());
    }
    public function export(Request $req):Response
    {
        try{[$year,$account]=$this->selection($req);$report=$this->reporting()->annual($year,$account);}
        catch(\DomainException){return new Response('Ungültiger Berichtsfilter.',422,['Content-Type'=>'text/plain; charset=utf-8']);}
        $type=$req->path==='/cash/export/csv'?'csv':'pdf';
        $bytes=$type==='csv'?(new CsvExporter())->export($report):(new PdfExporter())->export($report);
        $filename='vereinskasse-'.$report->year.($account!==null?'-konto-'.$account:'').'.'.$type;
        return new Response($bytes,200,[
            'Content-Type'=>$type==='csv'?'text/csv; charset=utf-8':'application/pdf',
            'Content-Disposition'=>'attachment; filename="'.$filename.'"',
            'Content-Length'=>(string)strlen($bytes),'Cache-Control'=>'private, no-store',
        ]);
    }
    public function upload(Request $req):Response
    {
        try{
            $id=$this->attachments()->upload(self::positive($req->inputString('entry')),
                $req->uploadedFile('receipt'),$this->user()->id);
            $msg='Beleg #'.$id.' hochgeladen.';
        }catch(\DomainException $e){$msg='Beleg abgelehnt: '.$e->getMessage();}
        $this->session->set('cash_flash',$msg);
        return Response::redirect($req->url('/cash'));
    }
    public function download(Request $req):Response
    {
        try{$result=$this->attachments()->download(self::positive($req->queryString('id')));}
        catch(\DomainException){return new Response('Beleg nicht gefunden.',404,['Content-Type'=>'text/plain; charset=utf-8','Cache-Control'=>'no-store']);}
        $mime=$result['receipt']->mime;
        // Never place the original filename in a header (CRLF and Unicode edge cases).
        $ext=match($mime){'application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',default=>'bin'};
        return new Response($result['bytes'],200,[
            'Content-Type'=>$mime,'X-Content-Type-Options'=>'nosniff',
            'Content-Disposition'=>'attachment; filename="beleg-'.$result['receipt']->id.'.'.$ext.'"',
            'Content-Length'=>(string)strlen($result['bytes']),'Cache-Control'=>'private, no-store',
        ]);
    }
}
