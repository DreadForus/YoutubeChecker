<?php

namespace YouTubeCheckerBundle\Service;

use YouTubeCheckerBundle\Exception\CurlServiceException;
use YouTubeCheckerBundle\Exception\RequestCurlServiceException;
use YouTubeCheckerBundle\Exception\ResponseCurlServiceException;

class CurlService
{
    /**
     * Create curl request and return resource $ch
     * @param string $path
     * @param array  $data
     * @param string $method
     * @param string $dataType
     * @param int    $timeout
     *
     * @return resource $ch
     * @throws RequestCurlServiceException
     */
    private function createRequest(
        string $path,
        array  $data     = [],
        string $method   = 'POST',
        string $dataType = 'JSON',
        int    $timeout  =  300
    ) {

        if (!filter_var($path, FILTER_VALIDATE_URL) === false) {
            $ch = curl_init($path);
        } else {
            throw new RequestCurlServiceException('Path "'.$path.'" is not a valid URL', 1006);
        }

        if (trim(strtolower($method)) == 'post') {

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

            if(trim(strtolower($dataType)) == 'json') {

                if($data_string = json_encode($data)){
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                    curl_setopt($ch, CURLOPT_HTTPHEADER,
                        [
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($data_string)
                        ]
                    );
                } else {
                    throw new RequestCurlServiceException('Failed encode data', 1004);
                }

            } else {
                throw new RequestCurlServiceException('Content-Type "'.$method.'" not allowed for POST method', 1004);
            }

        } elseif (trim(strtolower($method)) == 'get') {

            if (count($data) > 0) {
                foreach ($data as $key => $value) {

                    if (substr_count($path, '?') > 0) {
                        $path .= '&';
                    } else {
                        $path .= '?';
                    }

                    $path .= $key;

                    if (($value) and (trim($value) != '')) {
                        $path .='='.$value;
                    }
                }

                $ch = curl_init($path);
            }

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

            if(trim(strtolower($dataType)) == 'json') {
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            } else {
                throw new RequestCurlServiceException('Content-Type "'.$method.'" not allowed for GET method', 1004);
            }

        } else {
            throw new RequestCurlServiceException('Method "'.$method.'" not allowed', 1003);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        return $ch;
    }

    /**
     * Process single curl request and return response
     * @param string $path
     * @param array  $data
     * @param string $method
     * @param string $dataType
     * @param int    $timeout
     *
     * @return object $result
     * @throws CurlServiceException
     */
    public function load (
        string $path,
        array  $data     = [],
        string $method   = 'POST',
        string $dataType = 'JSON',
        int    $timeout  =  300
    ) {

//        dump([$path,$data,$method,$dataType,$timeout]);
//        die;
        try {

            $ch = $this->createRequest($path, $data, $method, $dataType, $timeout);

//            dump($ch);die;
            $result = curl_exec($ch);


            if (curl_errno($ch)) {
                throw new ResponseCurlServiceException(curl_error($ch), intval('11'.curl_errno($ch)));
            } else {

                if((curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 200)
                and(curl_getinfo($ch, CURLINFO_HTTP_CODE) < 300))
                {
                    if($responseObj = json_decode($result)){
                        return $responseObj;
                    } else {
                        throw new ResponseCurlServiceException('Failed json_encode response', 1007);
                    }
                } else {
                    throw new ResponseCurlServiceException('Unexpected response http code: '.curl_getinfo($ch, CURLINFO_HTTP_CODE).' ('.json_encode([$path, $data]).')', intval('12'.curl_getinfo($ch, CURLINFO_HTTP_CODE)));
                }
            }


        } catch(RequestCurlServiceException $e) {
            throw new CurlServiceException('Request creation failed', 1001, $e, func_get_args());
        } catch(ResponseCurlServiceException $e) {
            throw new CurlServiceException('Getting response failed', 1002, $e, func_get_args());
        } catch(\Exception $e) {
            throw new CurlServiceException($e->getMessage(), 1000, $e, func_get_args());
        }
    }


    public function loadMulti(array $resources)
    {
        try {
            $response = [];

            // build the multi-curl handle, adding both $ch
            $mh   = curl_multi_init();
            $chs  = [];
            $keys = [];
            foreach ($resources as $key => $value) {
                $keys [] = $key;
                $chs[$key] = $this->createRequest($value['path'], $value['data']);
                curl_setopt($chs[$key], CURLOPT_RETURNTRANSFER, true);
                curl_multi_add_handle($mh, $chs[$key]);
            }

            // execute all queries simultaneously, and continue when all are complete
            $running = null;
            do {
                curl_multi_exec($mh, $running);
            } while ($running);

            foreach ($keys as $key) {

                if ($responseObj = json_decode(curl_multi_getcontent($chs[$key]))) {
                    $response[] = $responseObj;
                } else {
                    throw new ResponseCurlServiceException('Failed json_encode response', 1007);
                }

                curl_multi_remove_handle($mh, $chs[$key]);
            }
            curl_multi_close($mh);

            return $response;
        } catch(RequestCurlServiceException $e) {
            throw new CurlServiceException('Request creation failed', 1001);
        } catch(ResponseCurlServiceException $e) {
            throw new CurlServiceException('Getting response failed', 1002);
        } catch(\Exception $e) {
            throw new CurlServiceException($e->getMessage(), 1000, $e, func_get_args());
        }
    }
}