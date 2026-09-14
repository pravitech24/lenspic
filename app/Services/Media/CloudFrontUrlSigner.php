<?php
namespace App\Services\Media;
use Aws\CloudFront\UrlSigner;
use Carbon\CarbonInterface;
use RuntimeException;
class CloudFrontUrlSigner {
 public function sign(string $objectKey,CarbonInterface $expiresAt,array $query=[]): string {if($expiresAt->isPast())throw new RuntimeException('Cannot sign an expired media URL.');$domain=trim((string)config('media.cloudfront.domain'));$keyId=trim((string)config('media.cloudfront.key_pair_id'));$keyPath=trim((string)config('media.cloudfront.private_key_path'));if($domain===''||$keyId===''||$keyPath==='')throw new RuntimeException('CloudFront signing is not configured.');if(str_contains($objectKey,'..')||str_starts_with($objectKey,'/'))throw new RuntimeException('Unsafe media key.');$url='https://'.$domain.'/'.implode('/',array_map('rawurlencode',explode('/',$objectKey)));if($query)$url.='?'.http_build_query($query,'','&',PHP_QUERY_RFC3986);return (new UrlSigner($keyId,$keyPath))->getSignedUrl($url,$expiresAt->timestamp); }
}
