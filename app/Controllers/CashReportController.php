<?php
/** Read-only cashbook report and export endpoints. Authorization is route middleware. */
declare(strict_types=1);
namespace Vereinssystem\Controllers;

use Vereinssystem\Auth\{AuthService,User};
use Vereinssystem\Core\{Csrf,DatabaseUnavailable,Request,Response,View};
use Vereinssystem\Reports\ReportRepository;
use Vereinssystem\Services\ReportService;

final class CashReportController extends Controller
{
    public function __construct(View $views,Csrf $csrf,private readonly ?AuthService $auth,
        private readonly ?ReportRepository $repository) {parent::__construct($views,$csrf);}
    private function service():ReportService
    {return new ReportService($this->repository??throw new DatabaseUnavailable('Berichte nicht verfügbar.'));}
    private function user():User
    {return $this->auth?->currentUser()??throw new \LogicException('Auth-Middleware fehlt.');}
    /** @return array{0:int,1:?int} */
    private function filter(Request $request):array
    {
        $year=filter_var($request->queryString('year',(string)date('Y')),FILTER_VALIDATE_INT);
        if(!is_int($year)||$year<2000||$year>2099)throw new \DomainException('Jahr ungültig.');
        $accountRaw=$request->queryString('account');
        if($accountRaw==='')return [$year,null];
        $id=filter_var($accountRaw,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if(!is_int($id))throw new \DomainException('Kontonummer ungültig.');
        return [$year,$id];
    }
    public function index(Request $request):Response
    {
        try{[$year,$account]=$this->filter($request);$report=$this->service()->annual($year,$account);}
        catch(\DomainException $e){return new Response(View::escape($e->getMessage()),400,['Content-Type'=>'text/plain; charset=utf-8']);}
        return $this->page($request,'Kassenauswertung','reports/cash',[
            'report'=>$report,'baseUrl'=>$request->url('/cash/report'),
            'csvUrl'=>$request->url('/cash/report/csv'),'pdfUrl'=>$request->url('/cash/report/pdf'),
            'cashUrl'=>$request->url('/cash'),
        ],$this->user());
    }
    public function export(Request $request):Response
    {
        try{[$year,$account]=$this->filter($request);$report=$this->service()->annual($year,$account);}
        catch(\DomainException $e){return new Response('Berichtsfilter ungültig.',400,['Content-Type'=>'text/plain; charset=utf-8']);}
        $file='vereinskasse-'.$year.($account!==null?'-konto-'.$account:'');
        $pdf=$request->path==='/cash/report/pdf';
        $body=$pdf?$this->service()->pdf($report):$this->service()->csv($report);
        return new Response($body,200,[
            'Content-Type'=>$pdf?'application/pdf':'text/csv; charset=utf-8',
            'Content-Disposition'=>'attachment; filename="'.$file.($pdf?'.pdf':'.csv').'"',
            'Cache-Control'=>'private, no-store',
        ]);
    }
}
