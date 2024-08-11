<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class Parsing extends BaseController
{
    public function index()
    {
        try{
            $resultList = []; // 결과 return용 array
            $dataString = ''; // 결과 계산하기 전 임시 temp
            $url = trim($this->request->getGet('url')); // 접속할 url

            if(isset($url)==false){
                throw new \Exception('url이 없습니다');
            }

            // curl 사용해서 링크의 html 문서 가저옴
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $output = curl_exec($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            if($errno){
                throw new \Exception('url에 접속할 수 없습니다.');
            }

            $textList = explode(PHP_EOL, $output);

            $flag = 0;
            foreach($textList as $i=>$text){
                // 1 . <body></body> 내부의 데이터만 필요
                if(preg_match('/<\/body/', $text)!=0){
                    break;
                }else if($flag==0){
                    if(preg_match('/<body/', strtolower($text))!=0){
                        $flag++;
                    }
                }else{
                    // 2. <body> 태그 내부의 <script>, <style> 태그도 제거
                    if($flag%2==1){
                        if(preg_match('/<script|<noscript|<style/', strtolower($text))!=0){
                            $flag++;
                        }else{
                            // 3. 태그를 제거하기 전에 하나의 문자열로 병합(태그를 끝내기 전에 줄바꿈하는 경우가 존재)
                            $dataString .= $text . ' ';
                        }
                    }else{
                        if(preg_match('/<\/script|<\/noscript|<\/style/', strtolower($text))!=0){
                            $flag++;
                        }
                    }
                }


            }

            // 공백제거
            $dataString = trim(preg_replace('/\&nbsp;|\t|\r/', '', strip_tags($dataString)));
            $dataStringList = explode(' ', $dataString);

            foreach($dataStringList as $dataString){
                if($dataString){
                    if(isset($resultList[$dataString])==false){
                        $resultList[$dataString] = 0;
                    }
                    $resultList[$dataString]++;
                }
            }

            // 내림차순 정렬
            arsort($resultList);
        }catch(\Exception $e){
            $resultList['message'] = $e->getMessage();
        }

        return $this->response->setJson($resultList);
    }
}