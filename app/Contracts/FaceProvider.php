<?php
namespace App\Contracts;
interface FaceProvider {public function resolveCollection(string$eventKey):string;public function indexFace(string$collectionId,string$externalId,string$imageBytes):array;public function search(string$collectionId,string$selfieBytes,float$threshold,int$maxResults):array;public function removeFaces(string$collectionId,array$faceIds):void;public function deleteCollection(string$collectionId):void;public function health():array;}
