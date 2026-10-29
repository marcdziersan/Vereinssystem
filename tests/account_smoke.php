<?php
/** Alpha 10.1 account domain checks with an in-memory repository. */
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use Vereinssystem\Accounts\{AccountRepository,AccountService};
use Vereinssystem\Auth\User;

final class FakeAccountRepository implements AccountRepository
{
    /** @var array<int,array{username:string,email:string,mobile:string}> */
    public array $profiles = [1 => ['username'=>'beispiel','email'=>'a@example.org','mobile'=>'']];
    /** @var array<int,string> */
    public array $hashes;
    public function __construct() { $this->hashes = [1=>password_hash('current-password-123',PASSWORD_DEFAULT)]; }
    public function profile(int $memberId): ?array { return $this->profiles[$memberId] ?? null; }
    public function updateProfile(int $memberId,string $expectedHash,string $username,string $email,string $mobile):bool
    {
        if (!hash_equals($this->hashes[$memberId]??'', $expectedHash)) return false;
        $this->profiles[$memberId]=['username'=>$username,'email'=>$email,'mobile'=>$mobile];return true;
    }
    public function changePassword(int $memberId,string $expectedHash,string $newHash):bool
    {
        if (!hash_equals($this->hashes[$memberId]??'', $expectedHash)) return false;
        $this->hashes[$memberId]=$newHash;return true;
    }
}

function assertAccount(bool $ok,string $message): void
{
    if (!$ok) { fwrite(STDERR,"FAIL: $message\n"); exit(1); }
    echo "OK: $message\n";
}
function expectAccountFailure(callable $action,string $message): void
{
    try { $action(); } catch (DomainException) { assertAccount(true,$message); return; }
    assertAccount(false,$message);
}

$repository = new FakeAccountRepository();
$service = new AccountService($repository);
$user = new User(1,'beispiel','M001','Eva','Beispiel',$repository->hashes[1],1,1);
assertAccount($service->profile($user)['username']==='beispiel','Own account can be read');
expectAccountFailure(fn()=> $service->updateProfile($user,'wrong','newuser','e@example.org',''), 'Current password required for profile');
expectAccountFailure(fn()=> $service->updateProfile($user,'current-password-123','unsafe<>','e@example.org',''), 'Invalid login name denied');
expectAccountFailure(fn()=> $service->updateProfile($user,'current-password-123','newuser','bad-address',''), 'Invalid email denied');
$service->updateProfile($user,'current-password-123','newuser','new@example.org','0171 111');
assertAccount($service->profile($user)['mobile']==='0171 111','Own profile updated');
expectAccountFailure(fn()=> $service->changePassword($user,'bad','new-password-123','new-password-123'), 'Wrong old password denied');
expectAccountFailure(fn()=> $service->changePassword($user,'current-password-123','short','short'), 'Short password denied');
expectAccountFailure(fn()=> $service->changePassword($user,'current-password-123','new-password-123','different-password-123'), 'Confirmation verified');
expectAccountFailure(fn()=> $service->changePassword($user,'current-password-123','current-password-123','current-password-123'), 'Password reuse denied');
$service->changePassword($user,'current-password-123','new-password-123','new-password-123');
assertAccount(password_verify('new-password-123',$repository->hashes[1]), 'New password stored as strong hash');
expectAccountFailure(fn()=> $service->changePassword($user,'current-password-123','another-password-123','another-password-123'), 'Stale hash cannot overwrite password');
$oldHash=md5('old-password-123');
$repository->hashes[1]=$oldHash;
$oldUser=new User(1,'newuser','M001','Eva','Beispiel',$oldHash,1,1);
$service->changePassword($oldUser,'old-password-123','new-password-456','new-password-456');
assertAccount(password_verify('new-password-456',$repository->hashes[1]), 'Historical MD5 account upgraded on password change');
echo "PASS: Alpha 10.1 account domain checks.\n";