<?php
namespace Tests\Unit\Services;
use App\Services\Media\CloudFrontUrlSigner;
use Tests\TestCase;
class CloudFrontUrlSignerTest extends TestCase {
 public function test_it_creates_a_short_lived_cloudfront_url_without_s3_host():void{$key=openssl_pkey_new(['private_key_bits'=>2048]);openssl_pkey_export($key,$pem);$path=tempnam(sys_get_temp_dir(),'cf-key-');file_put_contents($path,$pem);config(['media.cloudfront.domain'=>'media.example.test','media.cloudfront.key_pair_id'=>'KTEST','media.cloudfront.private_key_path'=>$path]);$url=app(CloudFrontUrlSigner::class)->sign('media/originals/groups/1/random.jpg',now()->addMinutes(5));@unlink($path);$this->assertStringStartsWith('https://media.example.test/media/originals/',$url);$this->assertStringContainsString('Expires=',$url);$this->assertStringContainsString('Signature=',$url);$this->assertStringContainsString('Key-Pair-Id=KTEST',$url);$this->assertStringNotContainsString('s3.',strtolower($url));}
}
