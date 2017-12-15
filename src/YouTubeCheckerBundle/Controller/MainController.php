<?php

namespace YouTubeCheckerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Exception;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class MainController extends Controller
{
    public function indexAction()
    {
        $form = $this->getSearchChanelForm();

        return $this->render('@YouTubeChecker/Home/index.html.twig', [
            "form" => $form->createView()
        ]);
    }

    public function resultAction(Request $request)
    {
//        dump($request);die;
        $query = $request->request->get("form")['query'];

        $result = $this->getResult($query);
//        dump($result);die;
        $form = $this->getSearchChanelForm();

        $form->handleRequest($request);
        return $this->render('@YouTubeChecker/Home/result.html.twig', [
            "form" => $form->createView(),
            'items' => $result

        ]);
    }

    private function getSearchChanelForm(): Form
    {
        return $this->createFormBuilder()
            ->add('query', TextType::class)
            ->add('search', SubmitType::class, array('label' => 'Search'))
            ->setAction($this->generateUrl("youtube_checker_result"))
            ->getForm();
    }

    private function getResult(string $query){

        $cachedItem = $this->get('cache.app')->getItem($query);

        if (!$cachedItem->isHit()) {
            $curlResult =  $this->channel($query);


            $cachedItem->set($curlResult);
            $this->get('cache.app')->save($cachedItem);
        } else {
            $curlResult = $cachedItem->get();
        }

//        dump($curlResult);
//        dump($curlResult->items);
//        die;

        return $curlResult->items;
    }

    private function search(string $query){
        $key = 'AIzaSyAPBWsj8grJPEJePYHd78Nx-4sbPF6Zi2o';
        $url = "https://www.googleapis.com/youtube/v3/search";

        $data = array(
            'key' => $key,
            'q' => $query,
            'part' => 'snippet',
            'type' => 'channel'
        );

        $curlService = $this->get("curl_service");

        return $curlService->load(
            $url,
            $data,
            "GET"
        );
    }

    private function channel(string $query){
        $key = 'AIzaSyAPBWsj8grJPEJePYHd78Nx-4sbPF6Zi2o';
        $url = "https://www.googleapis.com/youtube/v3/channels";

        $data = array(
            'key' => $key,
            'forUsername' => $query,
            'part' => 'snippet',
        );

        $curlService = $this->get("curl_service");

        try{
            return $curlService->load(
                $url,
                $data,
                "GET"
            );
        }catch (Exception $exception){
            var_dump($exception->getMessage());die;
        }
    }
}
