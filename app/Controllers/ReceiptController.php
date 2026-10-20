<?php
/** HTTP-only orchestration: metadata upload + authenticated binary download. */
declare(strict_types=1);
namespace Vereinssystem\Controllers;

use Vereinssystem\Auth\{AuthService,User};
use Vereinssystem\Core\{Csrf,DatabaseUnavailable,Request,Response,Session,View};
use Vereinssystem\Receipts\ReceiptRepository;
use Vereinssystem\Services\ReceiptService;

final class ReceiptController extends Controller
{
    public function __construct(View $views,Csrf $csrf,private readonly ?AuthService $auth,
        private readonly ?ReceiptRepository $repository,private readonly Session $session,private readonly string $storage)
    {parent::__construct($views,$csrf);}
    private function user():User{return $this->auth?->currentUser()??throw new \LogicException('Auth-Middleware fehlt.');}
    private function service():ReceiptService
    {return new ReceiptService($this->repository??throw new DatabaseUnavailable('Belege nicht verfügbar.'),$this->storage);}
    private static function id(string $text):int
    {
        $n=filter_var($text,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        return is_int($n)?$n:0;
    }
    public function upload(Request $request):Response
    {
        $this->user(); // Access policy and CSRF were already checked by route middleware.
        try{
            $id=$this->service()->upload(self::id($request->inputString('entry')),$request->file('receipt'),$this->user()->id);
            $message='Beleg #'.$id.' sicher gespeichert.';
        }catch(\DomainException $e){$message='Hinweis: '.$e->getMessage();}
        $this->session->set('cash_flash',$message);
        return Response::redirect($request->url('/cash'));
    }
    public function download(Request $request):Response
    {
        $this->user();
        try{$file=$this->service()->download(self::id($request->queryString('id')));}
        catch(\DomainException){return new Response('Beleg nicht gefunden.',404,['Content-Type'=>'text/plain; charset=utf-8','Cache-Control'=>'no-store']);}
        // RFC 5987 filename* supports German filenames; ASCII fallback contains no controls.
        $ascii=preg_replace('/[^A-Za-z0-9_.-]/','_', $file['filename'])?:'beleg';
        return new Response($file['content'],200,[
            'Content-Type'=>'application/octet-stream',
            'Content-Disposition'=>'attachment; filename="'.$ascii.'"; filename*=UTF-8\'\''.rawurlencode($file['filename']),
            'Cache-Control'=>'private, no-store',
            'Content-Length'=>(string)strlen($file['content']),
        ]);
    }
}
